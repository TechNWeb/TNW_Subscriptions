<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

/**
 * Class Totals
 */
class Totals extends Template
{
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get totals source object
     *
     * @return Order
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Create subscriptions initial fee totals summary
     *
     * @return $this
     */
    public function initTotals()
    {
        /** @var $items Item[] */
        $items = $this->getSource()->getAllItems();
        list($initialFeeTotal, $initialFeeBaseTotal) = $this->getTotalAmounts($items);
        if ($initialFeeTotal) {
            // Add our total information to the set of other totals
            $total = new DataObject(
                [
                    'code' => $this->getNameInLayout(),
                    'label' => __('Initial Fee(s)'),
                    'value' => $initialFeeTotal,
                    'base_value' => $initialFeeBaseTotal
                ]
            );
            if ($this->getBeforeCondition()) {
                $this->getParentBlock()->addTotalBefore($total, $this->getBeforeCondition());
            } else {
                $this->getParentBlock()->addTotal($total, $this->getAfterCondition());
            }
        }

        return $this;
    }

    /**
     * Returns subscription initial fees.
     *
     * @param Item[] $items
     * @return array
     */
    private function getTotalAmounts($items)
    {
        $result = 0;
        $baseResult = 0;
        foreach ($items as $item) {
            switch (true) {
                case $item instanceof \Magento\Sales\Model\Order\Item:
                    $qty = $item->getQtyOrdered();
                    break;

                case $item instanceof \Magento\Sales\Model\Order\Invoice\Item:
                    $qty = $item->getQty();
                    $item = $item->getOrderItem();
                    break;
                default:
                    continue 2;
            }

            $result +=  $this->getItemInitialFee($item) * $qty;
            $baseResult += $this->getItemBaseInitialFee($item) * $qty;
        }

        return [$result, $baseResult];
    }


    /**
     * Returns item subscription initial fee extension attribute.
     *
     * @param Item $item
     * @return int
     */
    private function getItemInitialFee($item)
    {
        $initialFee = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
        }

        return $initialFee;
    }

    /**
     * Returns item subscription base initial fee extension attribute.
     *
     * @param Item$item
     * @return int
     */
    private function getItemBaseInitialFee($item)
    {
        $baseInitialFee = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
        }

        return $baseInitialFee;
    }
}