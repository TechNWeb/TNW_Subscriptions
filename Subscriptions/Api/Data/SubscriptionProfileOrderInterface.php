<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileOrderInterface
{

    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const MAGENTO_ORDER_ID = 'magento_order_id';
    const ID = 'id';


    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param $id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     * @internal param string $id
     */
    public function setId($id);

    /**
     * Get subscription_profile_id
     * @return string|null
     */
    public function getSubscriptionProfileId();

    /**
     * Set subscription_profile_id
     * @param string $subscription_profile_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    public function setSubscriptionProfileId($subscription_profile_id);

    /**
     * Get magento_order_id
     * @return string|null
     */
    public function getMagentoOrderId();

    /**
     * Set magento_order_id
     * @param string $magento_order_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     */
    public function setMagentoOrderId($magento_order_id);
}
