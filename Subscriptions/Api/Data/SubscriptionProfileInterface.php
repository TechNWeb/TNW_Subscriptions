<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileInterface
{

    const WEBSITE_ID = 'website_id';
    const UNIT = 'unit';
    const CUSTOMER_ID = 'customer_id';
    const STATUS = 'status';
    const FREQUENCY = 'frequency';
    const ID = 'id';
    const LABEL = 'label';
    const BILLING_FREQUENCY_ID = 'billing_frequency_id';


    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param $id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     * @internal param string $id
     */
    public function setId($id);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customer_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setCustomerId($customer_id);

    /**
     * Get billing_frequency_id
     * @return string|null
     */
    public function getBillingFrequencyId();

    /**
     * Set billing_frequency_id
     * @param string $billing_frequency_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setBillingFrequencyId($billing_frequency_id);

    /**
     * Get label
     * @return string|null
     */
    public function getLabel();

    /**
     * Set label
     * @param string $label
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setLabel($label);

    /**
     * Get unit
     * @return string|null
     */
    public function getUnit();

    /**
     * Set unit
     * @param string $unit
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setUnit($unit);

    /**
     * Get website_id
     * @return string|null
     */
    public function getWebsiteId();

    /**
     * Set website_id
     * @param string $website_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setWebsiteId($website_id);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setStatus($status);

    /**
     * Get frequency
     * @return string|null
     */
    public function getFrequency();

    /**
     * Set frequency
     * @param string $frequency
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setFrequency($frequency);
}
