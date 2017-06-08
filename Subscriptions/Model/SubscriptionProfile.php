<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Model\AbstractModel;

class SubscriptionProfile extends AbstractModel implements SubscriptionProfileInterface
{
    const TNW_SUBSCRIPTION_CREATE_ORDER_ACTION_NAME = 'tnw_subscriptions_subscriptionprofile_edit';
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile');
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
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get customer_id
     * @return string
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param string $customer_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setCustomerId($customer_id)
    {
        return $this->setData(self::CUSTOMER_ID, $customer_id);
    }

    /**
     * Get billing_frequency_id
     * @return string
     */
    public function getBillingFrequencyId()
    {
        return $this->getData(self::BILLING_FREQUENCY_ID);
    }

    /**
     * Set billing_frequency_id
     * @param string $billing_frequency_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setBillingFrequencyId($billing_frequency_id)
    {
        return $this->setData(self::BILLING_FREQUENCY_ID,
            $billing_frequency_id);
    }

    /**
     * Get label
     * @return string
     */
    public function getLabel()
    {
        return $this->getData(self::LABEL);
    }

    /**
     * Set label
     * @param string $label
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setLabel($label)
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * Get unit
     * @return string
     */
    public function getUnit()
    {
        return $this->getData(self::UNIT);
    }

    /**
     * Set unit
     * @param string $unit
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setUnit($unit)
    {
        return $this->setData(self::UNIT, $unit);
    }

    /**
     * Get website_id
     * @return string
     */
    public function getWebsiteId()
    {
        return $this->getData(self::WEBSITE_ID);
    }

    /**
     * Set website_id
     * @param string $website_id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setWebsiteId($website_id)
    {
        return $this->setData(self::WEBSITE_ID, $website_id);
    }

    /**
     * Get status
     * @return string
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set status
     * @param string $status
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get frequency
     * @return string
     */
    public function getFrequency()
    {
        return $this->getData(self::FREQUENCY);
    }

    /**
     * Set frequency
     * @param string $frequency
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setFrequency($frequency)
    {
        return $this->setData(self::FREQUENCY, $frequency);
    }

    /**
     * Get engine code
     * @return string
     */
    public function getEngineCode()
    {
        return $this->getData(self::ENGINE_CODE);
    }

    /**
     * @param string $engine
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setEngineCode($engine)
    {
        return $this->setData(self::ENGINE_CODE, $engine);
    }

    /**
     * @return string
     */
    public function getBillingAddressId()
    {
        return $this->getData(self::BILLING_ADDRESS_ID);
    }

    /**
     * @param string $addressId
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setBillingAddressId($addressId)
    {
        return $this->setData(self::BILLING_ADDRESS_ID, $addressId);
    }

    /**
     * @return string
     */
    public function getShippingAddressId()
    {
        return $this->getData(self::SHIPPING_ADDRESS_ID);
    }

    /**
     * @param string $addressId
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     */
    public function setShippingAddressId($addressId)
    {
        return $this->setData(self::SHIPPING_ADDRESS_ID, $addressId);
    }
}
