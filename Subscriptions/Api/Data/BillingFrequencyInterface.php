<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface BillingFrequencyInterface
{
    const SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE = 'tnw_subscriptions_billing_frequency';


    const WEBSITE_ID = 'website_id';
    const UNIT = 'unit';
    const ID = 'id';
    const STATUS = 'status';
    const FREQUENCY = 'frequency';
    const LABEL = 'label';


    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param $id
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     * @internal param string $id
     */
    public function setId($id);

    /**
     * Get unit
     * @return string|null
     */
    public function getUnit();

    /**
     * Set unit
     * @param string $unit
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    public function setWebsiteId($website_id);

    /**
     * Get label
     * @return string|null
     */
    public function getLabel();

    /**
     * Set label
     * @param string $label
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    public function setLabel($label);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    public function setFrequency($frequency);
}
