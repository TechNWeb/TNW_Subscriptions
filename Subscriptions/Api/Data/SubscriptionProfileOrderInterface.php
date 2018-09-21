<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileOrderInterface
{
    /**#@+
     * Main table name.
     */
    const MAIN_TABLE = 'tnw_subscriptions_subscription_profile_order';
    /**#@-*/

    /**#@+
     * Constants for field names
     */
    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const MAGENTO_ORDER_ID = 'magento_order_id';
    const ID = 'id';
    const MAGENTO_QUOTE_ID = 'magento_quote_id';
    const SCHEDULED_AT = 'scheduled_at';
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

    /**
     * Gets magento quote id.
     *
     * @return string|null
     */
    public function getMagentoQuoteId();

    /**
     * Sets magento quote id.
     *
     * @param string $magentoQuoteId
     * @return $this
     */
    public function setMagentoQuoteId($magentoQuoteId);


    /**
     * Gets the scheduled date for order.
     *
     * @return string|null
     */
    public function getScheduledAt();

    /**
     * Sets the scheduled date for order.
     *
     * @param string $scheduledAt
     * @return $this
     */
    public function setScheduledAt($scheduledAt);
}
