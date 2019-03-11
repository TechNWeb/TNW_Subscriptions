<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\Total;

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

            $totalInitialFee += $itemInitialFees->getSubsInitialFee() * $item->getQty();
            $baseTotalInitialFee += $itemInitialFees->getBaseSubsInitialFee() * $item->getQty();
        }

        $total->setTotalAmount($this->getCode(), $totalInitialFee);
        $total->setBaseTotalAmount($this->getCode(), $baseTotalInitialFee);

        return $this;
    }

    /**
     * Returns quote item subscription initial fees extension attribute.
     *
     * @param CartItemInterface $item
     * @return QuoteItem
     */
    private function getItemInitialFees(CartItemInterface $item)
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
