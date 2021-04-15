<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TNW\Subscriptions\Api\Data\OrderItemExtensionAttributesInterface;

/**
 * Resource class for order item extension attribute.
 */
class OrderItem extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE,
            OrderItemExtensionAttributesInterface::MAGENTO_ITEM_ID
        );
    }
}
