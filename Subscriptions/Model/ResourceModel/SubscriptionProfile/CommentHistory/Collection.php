<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory as ResourceMessageHistory;

class Collection extends AbstractCollection
{
    /**
     * Define resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(MessageHistory::class, ResourceMessageHistory::class);
    }
}
