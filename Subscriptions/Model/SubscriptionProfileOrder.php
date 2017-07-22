<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder as Resource;

class SubscriptionProfileOrder extends AbstractModel
    implements SubscriptionProfileOrderInterface
{
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
    public function getMagentoOrderId()
    {
        return $this->getData(self::MAGENTO_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setMagentoOrderId($magentoOrderId)
    {
        return $this->setData(self::MAGENTO_ORDER_ID, $magentoOrderId);
    }
}
