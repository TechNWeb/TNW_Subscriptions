<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Creditmemo\Item\Resource;

use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item as ItemResource;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Credit memo item save plugin.
 */
class Save
{
    /**
     * Plugin around save credit memo item that saves subscription initial fees extension attribute.
     *
     * @param ItemResource $subject
     * @param \Closure $proceed
     * @param CreditmemoItemInterface $item
     * @return mixed
     */
    public function aroundSave(
        ItemResource $subject,
        \Closure $proceed,
        CreditmemoItemInterface $item
    ) {
        $result = $proceed($item);
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees && $initialFees->getBaseSubsInitialFee() > 0 && $initialFees->getSubsInitialFee() > 0) {
            $item->getResource()->getConnection()
                ->insertOnDuplicate(
                    $item->getResource()->getTable(SalesExtensionAttributesInterface::CREDITMEMO_ITEM_EXTENSION_TABLE),
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
