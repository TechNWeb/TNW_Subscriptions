<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder;

/**
 * Class Collection - SubscriptionProfileOrder ResourceModel
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\SubscriptionProfileOrder::class,
            \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder::class
        );
    }
}
