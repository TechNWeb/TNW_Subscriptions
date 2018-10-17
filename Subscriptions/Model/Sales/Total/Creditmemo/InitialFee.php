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
        $baseMaxInitialFee = $this->getMaxInitialFee($creditmemo);
        $baseRequestedFee = $this->getRequestedInitialFee($creditmemo);

        if ($baseRequestedFee > $baseMaxInitialFee) {
            throw new LocalizedException(
                __('Maximum initial fee allowed to refund is: %1', $baseMaxInitialFee)
            );
        }

        if (empty($baseRequestedFee)) {
            $baseRequestedFee = $baseMaxInitialFee;
        }

        $requestedFee = $creditmemo->roundPrice($baseRequestedFee);
        $maxInitialFee = $creditmemo->roundPrice($baseMaxInitialFee);

        /** @var CreditmemoItem $item */
        foreach ($creditmemo->getAllItems() as $item) {
            if ($item->getOrderItem()->isDummy() || !$this->hasInitialFeeToRefund($item)) {
                continue;
            }

            //this case needed when we refund last item or refund whole position
            list($currentFee, $baseCurrentFee) = $this->getItemCurrentInitialFees($item);
            $this->setItemInitialFees($item, $currentFee, $baseCurrentFee);

            $totalInitialFee += min($requestedFee, $maxInitialFee);
            $baseTotalInitialFee += min($baseRequestedFee, $baseMaxInitialFee);
        }

        $creditmemo->setData('subscription_initial_fee', $totalInitialFee);
        $creditmemo->setData('base_subscription_initial_fee', $baseTotalInitialFee);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $totalInitialFee);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTotalInitialFee);

        return $this;
    }

    /**
     * Returns order item subscription initial fees extension attribute.
     *
     * @param CreditmemoItem $item
     *
     * @return array
     */
    private function getItemCurrentInitialFees(CreditmemoItem $item)
    {
        $initialFee = 0;
        $baseInitialFee = 0;

        $initialFees = $this->getOrderItemInitialFees($item->getOrderItem());

        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
        }

        return [$initialFee, $baseInitialFee];
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
    protected function getMaxInitialFee(Creditmemo $creditmemo)
    {
        $totalInitialFees = 0;
        foreach ($creditmemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            $initialFees = $this->getOrderItemInitialFees($orderItem);

            if ($initialFees && !$item->getOrderItem()->isDummy()) {
                $totalInitialFees += (float)$initialFees->getBaseSubsInitialFee() * $orderItem->getQtyInvoiced();
            }
        }

        return $totalInitialFees - $creditmemo->getBaseAdjustment();
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
