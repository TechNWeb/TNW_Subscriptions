<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileStatusHistory;

/**
 * Collection resource model for subscription profile status history.
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\SubscriptionProfileStatusHistory::class,
            \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileStatusHistory::class
        );
    }
}
