<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Address as ResourceAddress;

class Address extends AbstractModel implements SubscriptionProfileAddressInterface
{
    const SUBSCRIPTION_PROFILE_ADDRESS_TABLE = 'tnw_subscriptions_subscription_profile_address';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceAddress::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddressType()
    {
        return $this->getData(static::ADDRESS_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setAddressType($addressType)
    {
        return $this->setData(self::ADDRESS_TYPE, $addressType);
    }

    /**
     * {@inheritdoc}
     */
    public function getCity()
    {
        return $this->getData(static::CITY);
    }

    /**
     * {@inheritdoc}
     */
    public function setCity($city)
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompany()
    {
        return $this->getData(static::COMPANY);
    }

    /**
     * {@inheritdoc}
     */
    public function setCompany($company)
    {
        return $this->setData(self::COMPANY, $company);
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryId()
    {
        return $this->getData(static::COUNTRY_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCountryId($id)
    {
        return $this->setData(self::COUNTRY_ID, $id);
    }

    /**
     * Gets customer address id.
     *
     * @return string
     */
    public function getCustomerAddressId()
    {
        return $this->getData(static::CUSTOMER_ADDRESS_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerAddressId($id)
    {
        return $this->setData(self::CUSTOMER_ADDRESS_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(static::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($addressId)
    {
        return $this->setData(self::ID, $addressId);
    }

    /**
     * {@inheritdoc}
     */
    public function getFax()
    {
        return $this->getData(static::FAX);
    }

    /**
     * {@inheritdoc}
     */
    public function setFax($fax)
    {
        return $this->setData(self::FAX, $fax);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstname()
    {
        return $this->getData(static::FIRSTNAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstname($firstname)
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastname()
    {
        return $this->getData(static::LASTNAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setLastname($lastname)
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlename()
    {
        return $this->getData(static::MIDDLENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setMiddlename($middlename)
    {
        return $this->setData(self::MIDDLENAME, $middlename);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfileId()
    {
        return $this->getData(static::PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setProfileId($id)
    {
        return $this->setData(self::PROFILE_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getPostcode()
    {
        return $this->getData(static::POSTCODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setPostcode($postcode)
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return $this->getData(static::PREFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix($prefix)
    {
        return $this->setData(self::PREFIX, $prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion()
    {
        return $this->getData(static::REGION);
    }

    /**
     * {@inheritdoc}
     */
    public function setRegion($region)
    {
        return $this->setData(self::REGION, $region);
    }

    /**
     * {@inheritdoc}
     */
    public function getRegionId()
    {
        return $this->getData(static::REGION_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setRegionId($id)
    {
        return $this->setData(self::REGION_ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStreet()
    {
        return $this->getData(static::STREET);
    }

    /**
     * {@inheritdoc}
     */
    public function setStreet($street)
    {
        return $this->setData(self::STREET, $street);
    }

    /**
     * {@inheritdoc}
     */
    public function getSuffix()
    {
        return $this->getData(static::SUFFIX);
    }

    /**
     * {@inheritdoc}
     */
    public function setSuffix($suffix)
    {
        return $this->setData(self::SUFFIX, $suffix);
    }

    /**
     * {@inheritdoc}
     */
    public function getTelephone()
    {
        return $this->getData(static::TELEPHONE);
    }

    /**
     * {@inheritdoc}
     */
    public function setTelephone($telephone)
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }
}
