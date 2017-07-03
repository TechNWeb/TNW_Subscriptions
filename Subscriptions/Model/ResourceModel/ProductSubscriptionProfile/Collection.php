<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile;

/**
 * Product subscription profile collection.
 */
class Collection extends \Magento\Eav\Model\Entity\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\ProductSubscriptionProfile::class,
            \TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile::class
        );
    }
}
