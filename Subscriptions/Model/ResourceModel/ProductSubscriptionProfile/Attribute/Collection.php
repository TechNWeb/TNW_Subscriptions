<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\Attribute;

/**
 * Product subscription profile attribute resource collection
 */
class Collection extends \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
{
    /**
     * Resource model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\ResourceModel\Eav\ProductSubscriptionProfileAttribute::class,
            \Magento\Eav\Model\ResourceModel\Entity\Attribute::class
        );
    }
}
