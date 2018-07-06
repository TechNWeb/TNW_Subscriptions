<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Subscription initial fee block for credit memo.
 */
class InitialFee extends \Magento\Backend\Block\Template
{
    /**
     * Source object
     *
     * @var \Magento\Framework\DataObject
     */
    private $source;

    /**
     * Initialize creditmemo totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->source = $parent->getSource();
        $total = new \Magento\Framework\DataObject(
            [
                'code' => 'tnw_subs_initial_fee',
                'block_name' => $this->getNameInLayout(),
            ]
        );
        $parent->addTotal($total, 'agjustments');

        return $this;
    }

    /**
     * Get source object
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Returns total subscription initial fee for credit memo.
     *
     * @return float|int
     */
    public function getBaseInitialFee()
    {
        $result = 0;
        /** @var CreditmemoItemInterface $item */
        foreach ($this->getSource()->getAllItems() as $item) {
            $initialFees = $item->getOrderItem()->getExtensionAttributes()
                ? $item->getOrderItem()->getExtensionAttributes()->getSubsInitialFees()
                : null;
            if ($initialFees) {
                $baseFee = (float)$initialFees->getBaseSubsInitialFee();
                $baseFeeRefunded = (float)$initialFees->getBaseSubsInitialFeeRefunded();
                $itemFee = $baseFee - $baseFeeRefunded;
                if ($itemFee > 0 && $item->getQty()) {
                    $result += $itemFee;
                }
            }
        }

        return $result;
    }

    /**
     * Returns total initial fee available to refund.
     *
     * @return float|int
     */
    public function getInitialFeeTotalValue()
    {
        $result = 0;
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->getSource()->getOrder();
        /** @var OrderItemInterface $item */
        foreach ($order->getAllItems() as $item) {
            $initialFees = $item->getExtensionAttributes()
                ? $item->getExtensionAttributes()->getSubsInitialFees()
                : null;
            if ($initialFees) {
                $baseFee = (float)$initialFees->getBaseSubsInitialFee();
                $baseFeeRefunded = (float)$initialFees->getBaseSubsInitialFeeRefunded();
                $itemFee = $baseFee - $baseFeeRefunded;
                if ($itemFee > 0) {
                    $result += $itemFee;
                }
            }
        }

        return __('Total: ') . $order->formatBasePrice($result);
    }
}
