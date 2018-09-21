<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote\Item;

use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Quote item save plugin.
 */
class AfterSave
{
    /**
     * Saves quote item subscription extension attributes.
     *
     * @param Item $subject
     * @param Item $result
     */
    public function afterSave(Item $subject, Item $result)
    {
        $initialFees = $result->getExtensionAttributes()
            ? $result->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees && $initialFees->getBaseSubsInitialFee() > 0 && $initialFees->getSubsInitialFee() > 0){
            $result->getResource()->getConnection()
                ->insertOnDuplicate(
                    $result->getResource()->getTable(SalesExtensionAttributesInterface::QUOTE_ITEM_EXTENSION_TABLE),
                    [
                        SalesExtensionAttributesInterface::MAGENTO_ITEM_ID =>
                            $result->getItemId(),
                        SalesExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE =>
                            $initialFees->getSubsInitialFee(),
                        SalesExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE =>
                            $initialFees->getBaseSubsInitialFee()
                    ]
                );
        }
    }
}
