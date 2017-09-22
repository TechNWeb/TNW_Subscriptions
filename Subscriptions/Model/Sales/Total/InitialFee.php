<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Subscription initial fee totals collector.
 */
class InitialFee extends AbstractTotal
{
    /**
     * Subscription initial fee totals collector.
     * Adds initial fee to grand total amount (without taxes).
     * Initial fee is quote item extension attribute.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $address = $shippingAssignment->getShipping()->getAddress();
        $quoteItems = $quote->getAllItems();
        $totalInitialFee = 0;
        $baseTotalInitialFee = 0;
        if ($quote->getItemsCount() > 0 && $quote->getId() && $address->getAddressType() === 'shipping') {
            /** @var Item $item */
            foreach ($quoteItems as $item) {
                list($initialFee, $baseInitialFee) = $this->getItemInitialFees($item);
                $totalInitialFee += $initialFee;
                $baseTotalInitialFee += $baseInitialFee;
            }
            $total->setTotalAmount($this->getCode(), $totalInitialFee);
            $total->setBaseTotalAmount($this->getCode(), $baseTotalInitialFee);
        }

        return $this;
    }

    /**
     * Returns quote item subscription initial fees extension attribute.
     *
     * @param Item $item
     * @return array
     */
    private function getItemInitialFees(Item $item)
    {
        $initialFee = 0;
        $baseInitialFee = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees){
            $initialFee = $initialFees->getSubsInitialFee();
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
        }

        return [$initialFee, $baseInitialFee];
    }
}