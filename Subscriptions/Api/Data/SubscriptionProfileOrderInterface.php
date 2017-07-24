<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileOrderInterface
{
    /**#@+
     * Constants for field names
     */
    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const MAGENTO_ORDER_ID = 'magento_order_id';
    const ID = 'id';
    /**#@-*/

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Sets  id.
     *
     * @param string $id
     * @return $this
     */
    public function setId($id);

    /**
     * Gets subscription profile id.
     *
     * @return string|null
     */
    public function getSubscriptionProfileId();

    /**
     * Sets subscription_profile_id.
     *
     * @param string $subscriptionProfileId
     * @return $this
     */
    public function setSubscriptionProfileId($subscriptionProfileId);

    /**
     * Gets magento order id.
     *
     * @return string|null
     */
    public function getMagentoOrderId();

    /**
     * Sets magento order id.
     *
     * @param string $magentoOrderId
     * @return $this
     */
    public function setMagentoOrderId($magentoOrderId);
}
