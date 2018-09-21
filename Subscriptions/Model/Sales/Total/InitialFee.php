<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

/**
 * Subscription initial fee totals collector.
 */
class InitialFee extends AbstractTotal
{
    /**
     * Constants for subscription initial fee tax object.
     */
    const ITEM_TYPE = 'subs_initial_fee';
    const ITEM_CODE = 'subs_initial_fee';

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
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $items = $shippingAssignment->getItems();
        if (!count($items) || !$quote->getId()) {
            return $this;
        }
        $totalInitialFee = 0;
        $baseTotalInitialFee = 0;
        /** @var AbstractItem $item */
        foreach ($items as $item) {
            $associatedTaxables = $item->getAssociatedTaxables();
            list($initialFee, $baseInitialFee) = $this->getItemInitialFees($item);
            $totalInitialFee += $initialFee;
            $baseTotalInitialFee += $baseInitialFee;
            $associatedTaxables[] = [
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE => self::ITEM_TYPE,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE => self::ITEM_TYPE,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE => $initialFee,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE => $baseInitialFee,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY => 1,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID => $item->getProduct()->getTaxClassId(),
            ];
            $item->setAssociatedTaxables($associatedTaxables);
        }

        $total->setTotalAmount($this->getCode(), $totalInitialFee);
        $total->setBaseTotalAmount($this->getCode(), $baseTotalInitialFee);

        return $this;
    }

    /**
     * Returns quote item subscription initial fees extension attribute.
     *
     * @param AbstractItem $item
     * @return array
     */
    private function getItemInitialFees(AbstractItem $item)
    {
        $initialFee = 0;
        $baseInitialFee = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
        }

        return [$initialFee, $baseInitialFee];
    }
}
