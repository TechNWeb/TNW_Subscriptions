<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Order\Item;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\ItemRepository;
use TNW\Subscriptions\Api\Data\OrderItemExtensionAttributesInterface;

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
                    $item->getResource()->getTable(OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE),
                    [
                        OrderItemExtensionAttributesInterface::MAGENTO_ITEM_ID =>
                            $item->getItemId(),
                        OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE =>
                            $initialFees->getSubsInitialFee(),
                        OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE =>
                            $initialFees->getBaseSubsInitialFee(),
                        OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE_INVOICED =>
                            $initialFees->getSubsInitialFeeInvoiced() ?: 0,
                        OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE_INVOICED =>
                            $initialFees->getBaseSubsInitialFeeInvoiced() ?: 0,
                        OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE_REFUNDED =>
                            $initialFees->getSubsInitialFeeRefunded() ?: 0,
                        OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE_REFUNDED =>
                            $initialFees->getBaseSubsInitialFeeRefunded() ?: 0,
                    ]
                );
        }
    }
}
