<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Invoice\Item;

use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item as ItemResource;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Invoice item save plugin.
 */
class ResourceSave
{
    /**
     * Plugin around save invoice item that saves subscription initial fees extension attribute.
     *
     * @param ItemResource $subject
     * @param \Closure $proceed
     * @param InvoiceItemInterface $item
     * @return mixed
     */
    public function aroundSave(
        ItemResource $subject,
        \Closure $proceed,
        InvoiceItemInterface $item
    ) {
        $result = $proceed($item);
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees && $initialFees->getBaseSubsInitialFee() > 0 && $initialFees->getSubsInitialFee() > 0) {
            $item->getResource()->getConnection()
                ->insertOnDuplicate(
                    $item->getResource()->getTable(SalesExtensionAttributesInterface::INVOICE_ITEM_EXTENSION_TABLE),
                    [
                        SalesExtensionAttributesInterface::MAGENTO_ITEM_ID =>
                            $item->getEntityId(),
                        SalesExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE =>
                            $initialFees->getSubsInitialFee(),
                        SalesExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE =>
                            $initialFees->getBaseSubsInitialFee()
                    ]
                );
        }

        return $result;
    }
}
