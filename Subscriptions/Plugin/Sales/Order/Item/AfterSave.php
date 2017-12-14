<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Order\Item;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\ItemRepository;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Quote item save plugin.
 */
class AfterSave
{
    /**
     * Plugin after save order item that saves subscription initial fees extension attribute.
     *
     * @param ItemRepository $itemRepository
     * @param OrderItemInterface $item
     */
    public function afterSave(
        ItemRepository $itemRepository,
        OrderItemInterface $item
    ) {
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees && $initialFees->getBaseSubsInitialFee() > 0 && $initialFees->getSubsInitialFee() > 0) {
            $item->getResource()->getConnection()
                ->insertOnDuplicate(
                    $item->getResource()->getTable(SalesExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE),
                    [
                        SalesExtensionAttributesInterface::MAGENTO_ITEM_ID =>
                            $item->getItemId(),
                        SalesExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE =>
                            $initialFees->getSubsInitialFee(),
                        SalesExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE =>
                            $initialFees->getBaseSubsInitialFee()
                    ]
                );
        }
    }
}