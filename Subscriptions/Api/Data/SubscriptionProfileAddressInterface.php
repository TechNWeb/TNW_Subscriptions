<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileAddressInterface
{
    /**#@+
     * Constants for profile address type
     */
    const ADDRESS_TYPE_SHIPPING = 'shipping';
    const ADDRESS_TYPE_BILLING = 'billing';
    /**#@-*/

    /**#@+
     * Constants for field names
     */
    const ID = 'id';
    const PROFILE_ID = 'profile_id';
    const CUSTOMER_ADDRESS_ID = 'customer_address_id';
    const REGION_ID = 'region_id';
    const FAX = 'fax';
    const REGION = 'region';
    const POSTCODE = 'postcode';
    const LASTNAME = 'lastname';
    const STREET = 'street';
    const CITY = 'city';
    const EMAIL = 'email';
    const TELEPHONE = 'telephone';
    const COUNTRY_ID = 'country_id';
    const FIRSTNAME = 'firstname';
    const ADDRESS_TYPE = 'address_type';
    const PREFIX = 'prefix';
    const MIDDLENAME = 'middlename';
    const SUFFIX = 'suffix';
    const COMPANY = 'company';
    /**#@-*/

    /**
     * Gets the address type for the profile address.
     *
     * @return string Address type.
     */
    public function getAddressType();

    /**
     * Sets the address type for the profile address.
     *
     * @param string $addressType
     * @return $this
     */
    public function setAddressType($addressType);

    /**
     * Gets the city for the profile address.
     *
     * @return string City.
     */
    public function getCity();

    /**
     * Sets the city for the profile address.
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city);

    /**
     * Gets the company for the profile address.
     *
     * @return string|null Company.
     */
    public function getCompany();


    /**
     * Sets the company for the profile address.
     *
     * @param string $company
     * @return $this
     */
    public function setCompany($company);

    /**
     * Gets the country ID for the profile address.
     *
     * @return string Country ID.
     */
    public function getCountryId();

    /**
     * Sets the country ID for the profile address.
     *
     * @param string $id
     * @return $this
     */
    public function setCountryId($id);

    /**
     * Gets the country address ID for the profile address.
     *
     * @return int|null Country address ID.
     */
    public function getCustomerAddressId();

    /**
     * Sets the customer address ID for the profile address.
     *
     * @param int $id
     * @return $this
     */
    public function setCustomerAddressId($id);

    /**
     * Gets the ID for the profile address.
     *
     * @return int|null Order address ID.
     */
    public function getId();

    /**
     * Sets the ID for the profile address.
     *
     * @param int $addressId
     * @return $this
     */
    public function setId($addressId);

    /**
     * Gets the fax number for the profile address.
     *
     * @return string|null Fax number.
     */
    public function getFax();

    /**
     * Sets the fax number for the profile address.
     *
     * @param string $fax
     * @return $this
     */
    public function setFax($fax);

    /**
     * Gets the first name for the profile address.
     *
     * @return string First name.
     */
    public function getFirstname();

    /**
     * Sets the first name for the profile address.
     *
     * @param string $firstname
     * @return $this
     */
    public function setFirstname($firstname);

    /**
     * Gets the last name for the profile address.
     *
     * @return string Last name.
     */
    public function getLastname();

    /**
     * Sets the last name for the profile address.
     *
     * @param string $lastname
     * @return $this
     */
    public function setLastname($lastname);

    /**
     * Gets the middle name for the profile address.
     *
     * @return string|null Middle name.
     */
    public function getMiddlename();

    /**
     * Sets the middle name for the profile address.
     *
     * @param string $middlename
     * @return $this
     */
    public function setMiddlename($middlename);

    /**
     * Gets the subscription profile ID for the profile address.
     *
     * @return int|null Parent ID.
     */
    public function getProfileId();

    /**
     * Sets the parent ID for the profile address.
     *
     * @param int $id
     * @return $this
     */
    public function setProfileId($id);

    /**
     * Gets the postal code for the profile address.
     *
     * @return string Postal code.
     */
    public function getPostcode();

    /**
     * Sets the postal code for the profile address.
     *
     * @param string $postcode
     * @return $this
     */
    public function setPostcode($postcode);

    /**
     * Gets the prefix for the profile address.
     *
     * @return string|null Prefix.
     */
    public function getPrefix();

    /**
     * Sets the prefix for the profile address.
     *
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix);

    /**
     * Gets the region for the profile address.
     *
     * @return string|null Region.
     */
    public function getRegion();

    /**
     * Sets the region for the profile address.
     *
     * @param string $region
     * @return $this
     */
    public function setRegion($region);

    /**
     * Gets the region ID for the profile address.
     *
     * @return int|null Region ID.
     */
    public function getRegionId();

    /**
     * Sets the region ID for the profile address.
     *
     * @param int $id
     * @return $this
     */
    public function setRegionId($id);

    /**
     * Gets the street values, if any, for the profile address.
     *
     * @return string[]|null Array of any street values. Otherwise, null.
     */
    public function getStreet();

    /**
     * Sets the street values, if any, for the profile address.
     *
     * @param string|string[] $street
     * @return $this
     */
    public function setStreet($street);

    /**
     * Gets the suffix for the profile address.
     *
     * @return string|null Suffix.
     */
    public function getSuffix();

    /**
     * Sets the suffix for the profile address.
     *
     * @param string $suffix
     * @return $this
     */
    public function setSuffix($suffix);

    /**
     * Gets the telephone number for the profile address.
     *
     * @return string Telephone number.
     */
    public function getTelephone();

    /**
     * Sets the telephone number for the profile address.
     *
     * @param string $telephone
     * @return $this
     */
    public function setTelephone($telephone);

    /**
     * Exports customer address
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    public function exportCustomerAddress();
}
