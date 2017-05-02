<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

class SubscriptionProfileOrder extends \Magento\Framework\Model\AbstractModel implements SubscriptionProfileOrderInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder');
    }

    /**
     * Get id
     * @return string
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set id
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get subscription_profile_id
     * @return string
     */
    public function getSubscriptionProfileId()
    {
        return $this->getData(self::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * Set subscription_profile_id
     * @param string $subscription_profile_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    public function setSubscriptionProfileId($subscription_profile_id)
    {
        return $this->setData(self::SUBSCRIPTION_PROFILE_ID, $subscription_profile_id);
    }

    /**
     * Get magento_order_id
     * @return string
     */
    public function getMagentoOrderId()
    {
        return $this->getData(self::MAGENTO_ORDER_ID);
    }

    /**
     * Set magento_order_id
     * @param string $magento_order_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    public function setMagentoOrderId($magento_order_id)
    {
        return $this->setData(self::MAGENTO_ORDER_ID, $magento_order_id);
    }
}
