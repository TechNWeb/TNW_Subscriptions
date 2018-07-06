<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model class to log messages for Subscription Profile.
 */
class MessageHistory extends AbstractDb
{
    /**
     * Define resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tnw_subscriptions_subscription_profile_message_history', 'entity_id');
    }
}
