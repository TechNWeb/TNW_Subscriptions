<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;

/**
 * Class BillingFrequency billing frequency model
 */
class BillingFrequency extends \Magento\Framework\Model\AbstractModel implements BillingFrequencyInterface
{
    /**
     * The list of allowed for modification attributes in any scenarios
     *
     * @var array
     */
    private $allowedForModificationAttributes = [
        self::STATUS
    ];

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\BillingFrequency::class);
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    public function setWebsiteId($website_id)
    {
        return $this->setData(self::WEBSITE_ID, $website_id);
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    public function setLabel($label)
    {
        return $this->setData(self::LABEL, $label);
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
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
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     */
    public function setFrequency($frequency)
    {
        return $this->setData(self::FREQUENCY, $frequency);
    }

    /**
     * @param $attributeCode
     * @return bool
     */
    public function isAllowedModification($attributeCode)
    {
        return in_array($attributeCode, $this->allowedForModificationAttributes);
    }
}
