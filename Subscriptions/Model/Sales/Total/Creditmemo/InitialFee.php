<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\Total\Creditmemo;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Magento\Sales\Model\Order\Item as OrderItem;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem as CreditmemoItemFeeExtension;

/**
 * Credit memo initial fee totals collector.
 */
class InitialFee extends AbstractTotal
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     * @param array $data
     */
    public function __construct(
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($data);
        $this->request = $request;
    }


    /**
     * Subscription initial fee totals collector.
     * Adds initial fee to grand total amount (without taxes).
     * Initial fee is order item extension attribute.
     *
     * @param Creditmemo $creditmemo
     * @return $this
     * @throws LocalizedException
     */
    public function collect(Creditmemo $creditmemo)
    {
        $totalInitialFee = 0;
        $baseTotalInitialFee = 0;
        $summaryInitialFee = $this->getSummaryInitialFee($creditmemo);
        $baseRequestedFee = $this->getRequestedInitialFee($creditmemo);

        if ($baseRequestedFee > $summaryInitialFee) {
            throw new LocalizedException(
                __('Maximum initial fee allowed to refund is: %1', $summaryInitialFee)
            );
        }

        $requestedFee = ($baseRequestedFee >= 0) ? $creditmemo->roundPrice($baseRequestedFee) : false;
        /** @var CreditmemoItem $item */
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItemQty = $item->getOrderItem()->getQtyOrdered();
            $orderItemQtyRefunded = $item->getOrderItem()->getQtyRefunded();

            if ($item->getOrderItem()->isDummy() || !$this->hasInitialFeeToRefund($item) || !$orderItemQty) {
                continue;
            }

            $currentFee = 0;
            $baseCurrentFee = 0;
            if (in_array($item->getQty(), [$orderItemQty, $orderItemQty - $orderItemQtyRefunded])) {
                //this case needed when we refund last item or refund whole position
                list($currentFee, $baseCurrentFee) = $this->getItemCurrentInitialFees($item);
                if ($baseRequestedFee) {
                    //case when there is initial fee to refund
                    $baseCurrentFee = min($baseRequestedFee, $baseCurrentFee);
                    $currentFee = min($requestedFee, $currentFee);
                    $baseRequestedFee = ($baseRequestedFee >= $baseCurrentFee)
                        ? $baseRequestedFee - $baseCurrentFee
                        : 0;
                    $requestedFee = $creditmemo->roundPrice($baseRequestedFee);
                    $summaryInitialFee = ($summaryInitialFee >= $currentFee)
                        ? $summaryInitialFee - $currentFee
                        : 0;
                } elseif ($baseRequestedFee === 0) {
                    //case when we create credit memo but there is no initial fee to refund
                    $currentFee = 0;
                    $baseCurrentFee = 0;
                }
            } elseif ($item->getQty()) {
                //case when we partially refund item
                list($currentFee, $baseCurrentFee) = $this->getItemCurrentInitialFees(
                    $item,
                    $summaryInitialFee,
                    $requestedFee
                );
            }

            $this->setItemInitialFees($item, $currentFee, $baseCurrentFee);
            $totalInitialFee += $currentFee;
            $baseTotalInitialFee += $baseCurrentFee;
        }

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $totalInitialFee);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTotalInitialFee);

        return $this;
    }

    /**
     * Returns order item subscription initial fees extension attribute.
     *
     * @param CreditmemoItem $item
     * @param float|null $summaryInitialFee
     * @param float|null $requestedFee
     * @return array
     */
    private function getItemCurrentInitialFees(
        CreditmemoItem $item,
        $summaryInitialFee = null,
        $requestedFee = null
    ) {
        $initialFee = 0;
        $baseInitialFee = 0;
        $initialFeeRefunded = 0;
        $baseInitialFeeRefunded = 0;
        $ratio = 1;
        $initialFees = $this->getOrderItemInitialFees($item->getOrderItem());
        if ($requestedFee && $summaryInitialFee) {
            $ratio = $requestedFee / $summaryInitialFee;
        }

        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
            $initialFeeRefunded = $initialFees->getSubsInitialFeeRefunded();
            $baseInitialFeeRefunded = $initialFees->getBaseSubsInitialFeeRefunded();
        }

        $currentFee = $item->getCreditmemo()->roundPrice(
            ($initialFee - $initialFeeRefunded) * $ratio,
            'base'
        );
        $currentBaseFee = $item->getCreditmemo()->roundPrice(
            ($baseInitialFee - $baseInitialFeeRefunded) * $ratio
        );

        return [$currentFee, $currentBaseFee];
    }

    /**
     * Sets to credit memo item subscription initial fee.
     *
     * @param CreditmemoItem $item
     * @param float $fee
     * @param float $baseFee
     * @return void
     */
    private function setItemInitialFees(CreditmemoItem $item, $fee, $baseFee)
    {
        if ($item->getExtensionAttributes()
            && $item->getExtensionAttributes()->getSubsInitialFees()
        ) {
            $item->getExtensionAttributes()->getSubsInitialFees()
                ->setSubsInitialFee($fee)
                ->setBaseSubsInitialFee($baseFee);
        }
    }

    /**
     * Returns total initial fee for credit memo.
     *
     * @param Creditmemo $creditmemo
     * @return float|int
     */
    protected function getSummaryInitialFee(Creditmemo $creditmemo)
    {
        $totalInitialFees = 0;
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItemInitialFees = $this->getOrderItemInitialFees($item->getOrderItem());
            if ($orderItemInitialFees && !$item->getOrderItem()->isDummy() && $item->getQty() > 0) {
                $baseItemFee = (float)$orderItemInitialFees->getBaseSubsInitialFee();
                $baseFeeRefunded = (float)$orderItemInitialFees->getBaseSubsInitialFeeRefunded();
                $totalInitialFees += $baseItemFee - $baseFeeRefunded;
            }
        }

        return $totalInitialFees;
    }

    /**
     * Returns subscription initial fee to refund from request.
     *
     * @param Creditmemo $creditmemo
     * @return bool|float
     */
    private function getRequestedInitialFee(Creditmemo $creditmemo)
    {
        $requestMemo = $this->request->getParam('creditmemo', []);
        $result = false;

        if (isset($requestMemo['subscription_initial_fee'])) {
            $result = 0;
            if ($requestMemo['subscription_initial_fee'] > 0) {
                $result = $creditmemo->roundPrice($requestMemo['subscription_initial_fee'], 'base');
            }
        }

        return $result;
    }

    /**
     * Returns order item initial fees extension attribute.
     *
     * @param OrderItem $item
     * @return null|CreditmemoItemFeeExtension
     */
    private function getOrderItemInitialFees(OrderItem $item)
    {
        $orderItemInitialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;

        return $orderItemInitialFees;
    }

    /**
     * Checks if credit memo item has initial fee to refund.
     *
     * @param CreditmemoItem $item
     * @return bool
     */
    private function hasInitialFeeToRefund(CreditmemoItem $item)
    {
        $result = false;
        $orderItemInitialFees = $this->getOrderItemInitialFees($item->getOrderItem());

        if ($orderItemInitialFees) {
            $baseFee = $orderItemInitialFees->getBaseSubsInitialFee();
            $baseFeeRefunded = $orderItemInitialFees->getBaseSubsInitialFeeRefunded();
            $result = $item->getQty() && ($baseFee - $baseFeeRefunded > 0);
        }

        return $result;
    }
}
