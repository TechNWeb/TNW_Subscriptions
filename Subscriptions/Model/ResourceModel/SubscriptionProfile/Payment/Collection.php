<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment;

use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;

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
            \TNW\Subscriptions\Model\SubscriptionProfile\Payment::class,
            \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment::class
        );
    }

    /**
     * Sets subscription profile filter to result.
     *
     * @param string|int $subscriptionId
     * @return $this
     */
    public function setSubscriptionProfileFilter($subscriptionId)
    {
        return $this->addFieldToFilter(SubscriptionProfilePaymentInterface::PROFILE_ID, $subscriptionId);
    }
}
