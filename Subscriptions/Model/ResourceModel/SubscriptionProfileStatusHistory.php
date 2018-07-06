<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfileStatusHistory as Model;

/**
 * Resource model for subscription profile status history.
 */
class SubscriptionProfileStatusHistory extends AbstractDb
{
    /**
     * Define resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Model::TABLE,
            SubscriptionProfileStatusHistoryInterface::ID
        );
    }
}
