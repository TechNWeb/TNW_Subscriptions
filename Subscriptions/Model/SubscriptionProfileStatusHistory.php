<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder as Resource;

/**
 * Model for subscription profile status history.
 */
class SubscriptionProfileStatusHistory extends AbstractModel
    implements SubscriptionProfileStatusHistoryInterface
{
    /**#@+
     * Main table name.
     */
    const TABLE = 'tnw_subscriptions_subscription_profile_status_history';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionProfileId()
    {
        return $this->getData(self::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setSubscriptionProfileId($subscriptionProfileId)
    {
        return $this->setData(self::SUBSCRIPTION_PROFILE_ID, $subscriptionProfileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusOld()
    {
        return $this->getData(static::STATUS_OLD);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatusOld($status)
    {
        return $this->setData(self::STATUS_OLD, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusNew()
    {
        return $this->getData(static::STATUS_NEW);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatusNew($status)
    {
        return $this->setData(self::STATUS_NEW, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId()
    {
        return $this->getData(static::USER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setUserId($id)
    {
        return $this->setData(self::USER_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return $this->getData(static::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($id)
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getChangedAt()
    {
        return $this->getData(static::CHANGED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setChangedAt($changedAt)
    {
        return $this->setData(self::CHANGED_AT, $changedAt);
    }
}
