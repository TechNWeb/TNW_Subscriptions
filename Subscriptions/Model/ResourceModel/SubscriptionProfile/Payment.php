<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;

use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;

/**
 * Resource model for subscription profile payments
 */
class Payment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            SubscriptionProfilePaymentInterface::SUBSCRIPTIONS_PROFILE_PAYMENT_TABLE,
            'payment_id'
        );
    }
}
