<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\SubscriptionProfileQueueInterface;
use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Model\ResourceModel\Queue as Resource;

/**
 * Class Queue
 */
class Queue extends AbstractModel implements SubscriptionProfileQueueInterface
{
    /**#@+
     * Main table name.
     */
    const SUBSCRIPTION_PROFILE_QUEUE_TABLE = 'tnw_subscriptions_subscription_profile_queue';
    /**#@-*/

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileOrderId()
    {
        return $this->getData(static::PROFILE_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setProfileOrderId($profileOrderId)
    {
        return $this->setData(self::PROFILE_ORDER_ID, $profileOrderId);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->getData(static::STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttemptCount()
    {
        return $this->getData(static::ATTEMPT_COUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setAttemptCount($count)
    {
        return $this->setData(self::ATTEMPT_COUNT, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->getData(static::MESSAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(static::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt()
    {
        return $this->getData(static::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}