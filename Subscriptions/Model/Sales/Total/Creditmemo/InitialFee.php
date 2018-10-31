<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Sales\Total\Creditmemo;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Credit memo initial fee totals collector.
 */
class InitialFee extends AbstractTotal
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($data);
        $this->priceCurrency = $priceCurrency;
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
        $order = $creditmemo->getOrder();

        $orderInitialFeeAmount = $this->getOrderInitialFee($order, 'getSubsInitialFee');
        $orderBaseInitialFeeAmount = $this->getOrderInitialFee($order, 'getBaseSubsInitialFee');
        $allowedAmount = $orderInitialFeeAmount
            - $order->getExtensionAttributes()->getSubscriptionInitialFeeRefunded();
        $baseAllowedAmount = $orderBaseInitialFeeAmount
            - $order->getExtensionAttributes()->getBaseSubscriptionInitialFeeRefunded();

        /** @var \Magento\Sales\Api\Data\CreditmemoExtensionInterface $extensionAttributes */
        $extensionAttributes = $creditmemo->getExtensionAttributes();

        if ($extensionAttributes->getBaseSubscriptionInitialFee()) {
            $desiredAmount = $this->priceCurrency->round($extensionAttributes->getBaseSubscriptionInitialFee());

            // Note: ($x < $y + 0.0001) means ($x <= $y) for floats
            if ($desiredAmount < $this->priceCurrency->round($baseAllowedAmount) + 0.0001) {
                $ratio = 0;
                if ($orderBaseInitialFeeAmount > 0) {
                    $ratio = $desiredAmount / $orderBaseInitialFeeAmount;
                }

                // Note: ($x > $y - 0.0001) means ($x >= $y) for floats
                if ($desiredAmount > $baseAllowedAmount - 0.0001) {
                    $totalInitialFee = $allowedAmount;
                    $baseTotalInitialFee = $baseAllowedAmount;
                } else {
                    $totalInitialFee = $this->priceCurrency->round($orderInitialFeeAmount * $ratio);
                    $baseTotalInitialFee = $this->priceCurrency->round($orderBaseInitialFeeAmount * $ratio);
                }
            } else {
                $baseAllowedAmount = $order->getBaseCurrency()->format($baseAllowedAmount, null, false);
                throw new LocalizedException(__('Maximum initial fee allowed to refund is: %1', $baseAllowedAmount));
            }
        } else {
            $totalInitialFee = $allowedAmount;
            $baseTotalInitialFee = $baseAllowedAmount;
        }

        $extensionAttributes->setSubscriptionInitialFee($totalInitialFee);
        $extensionAttributes->setBaseSubscriptionInitialFee($baseTotalInitialFee);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $totalInitialFee);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTotalInitialFee);

        return $this;
    }

    /**
     * Returns total initial fee for credit memo.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $method
     *
     * @return float|int
     */
    protected function getOrderInitialFee($order, $method)
    {
        $totalInitialFees = 0;
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $extensionAttributes = $orderItem->getExtensionAttributes();
            if (!$extensionAttributes instanceof \Magento\Sales\Api\Data\OrderItemExtensionInterface) {
                continue;
            }

            $initialFees = $extensionAttributes->getSubsInitialFees();
            if (!$initialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem) {
                continue;
            }

            if ($orderItem->isDummy()) {
                continue;
            }

            $totalInitialFees += (float)$initialFees->$method() * $orderItem->getQtyInvoiced();
        }

        return $totalInitialFees;
    }
}
