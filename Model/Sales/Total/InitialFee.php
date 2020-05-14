<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\Total;

use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Quote\Api\Data\CartItemExtensionInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\QuoteItem;

/**
 * Subscription initial fee totals collector.
 */
class InitialFee extends AbstractTotal
{
    /**
     * Subscription initial fee totals collector.
     * Adds initial fee to grand total amount (without taxes).
     * Taxes calculated as separated tax object.
     * Initial fee is quote item extension attribute.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);
        if (!$shippingAssignment->getItems() || $quote->getScheduled()) {
            return $this;
        }

        $totalInitialFee = 0;
        $baseTotalInitialFee = 0;

        foreach ($shippingAssignment->getItems() as $item) {
            $itemInitialFees = $this->getItemInitialFees($item);
            if (null === $itemInitialFees) {
                continue;
            }

            if (Configurable::TYPE_CODE === $item->getProductType()) {
                $totalInitialFee -= $itemInitialFees->getSubsInitialFee();
                $baseTotalInitialFee -= $itemInitialFees->getBaseSubsInitialFee();
            }

            $totalInitialFee += $itemInitialFees->getSubsInitialFee() * $item->getQty();
            $baseTotalInitialFee += $itemInitialFees->getBaseSubsInitialFee() * $item->getQty();
        }
        $total->setTotalAmount($this->getCode(), $totalInitialFee);
        $total->setBaseTotalAmount($this->getCode(), $baseTotalInitialFee);
        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $amount = 0;
        $quoteItems = $quote->getItems();
        if (is_array($quoteItems)) {
            foreach ($quote->getItems() as $item) {
                $itemInitialFees = $this->getItemInitialFees($item);
                if (null === $itemInitialFees) {
                    continue;
                }
                $amount += $itemInitialFees->getSubsInitialFee() * $item->getQty();
            }
        }
        return [
            'code'  => 'subs_initial_fee',
            'title' => __('Initial Fee'),
            'value' => $amount ? $amount : NULL
        ];
    }

    /**
     * Get label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Initial Fee');
    }

    /**
     * Returns quote item subscription initial fees extension attribute.
     *
     * @param CartItemInterface | ItemInterface $item
     * @return QuoteItem
     */
    private function getItemInitialFees($item)
    {
        $extensionAttributes = $item->getExtensionAttributes();
        if (!$extensionAttributes instanceof CartItemExtensionInterface) {
            return null;
        }

        $initialFees = $extensionAttributes->getSubsInitialFees();
        if (!$initialFees instanceof QuoteItem) {
            return null;
        }

        return $initialFees;
    }
}
