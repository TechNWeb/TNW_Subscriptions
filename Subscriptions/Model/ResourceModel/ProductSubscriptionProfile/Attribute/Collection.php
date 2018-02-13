<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\Attribute;

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
