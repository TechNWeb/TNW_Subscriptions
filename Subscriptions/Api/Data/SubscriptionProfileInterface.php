<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\CustomAttributesDataInterface;

/**
 * Interface SubscriptionProfileInterface
 */
interface SubscriptionProfileInterface extends CustomAttributesDataInterface
{
    /**#@+
     * Constants for field names
     */
    const WEBSITE_ID = 'website_id';
    const UNIT = 'unit';
    const CUSTOMER_ID = 'customer_id';
    const STATUS = 'status';
    const FREQUENCY = 'frequency';
    const ID = 'entity_id';
    const LABEL = 'label';
    const BILLING_FREQUENCY_ID = 'billing_frequency_id';
    const ENGINE_CODE = 'engine_code';
    const START_DATE = 'start_date';
    const TRIAL_START_DATE = 'trial_start_date';
    const TERM = 'term';
    const TOTAL_BILLING_CYCLES = 'total_billing_cycles';
    const SHIPPING_METHOD = 'shipping_method';
    const SHIPPING_DESCRIPTION = 'shipping_description';
    const PROFILE_CURRENCY_CODE = 'profile_currency_code';
    const TRIAL_LENGTH = 'trial_length';
    const TRIAL_LENGTH_UNIT = 'trial_length_unit';
    const IS_VIRTUAL = 'is_virtual';
    const TOKEN_HASH = 'token_hash';
    const PAYMENT_ADDITIONAL_INFO = 'payment_additional_info';
    /**
     * @deprecated use GENERATE_QUOTES_STATE
     */
    const NEED_GENERATE_QUOTES = 'need_generate_quotes';
    const GENERATE_QUOTES_STATE = 'generate_quotes_state';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const NEED_RECOLLECT = 'need_recollect';
    const CANCEL_BEFORE_NEXT_CYCLE = 'cancel_before_next_cycle';
    /**#@-*/

    /**#@+
     * Constants for subscription profile addresses
     */
    const PROFILE_ADDRESSES = 'profile_addresses';
    const PROFILE_SHIPPING_ADDRESSES = 'profile_billing_address';
    const PROFILE_BILLING_ADDRESSES = 'profile_shipping_address';
    /**#@-*/

    /**#@+
     * Constant for subscription profile products
     */
    const PROFILE_PRODUCTS = 'profile_products';
    const PROFILE_VISIBLE_PRODUCTS = 'profile_visible_products';
    /**#@-*/

    /**
     * Label prefix
     */
    const LABEL_PREFIX = '#S-';

    /**#@+
     * Generate quotes states.
     */
    const GENERATE_QUOTES_STATE_GENERATED = 0;
    const GENERATE_QUOTES_STATE_NEED_GENERATE = 1;
    const GENERATE_QUOTES_STATE_GENERATED_FOR_YEAR = 2;
    /**#@-*/

    /**
     * Gets id.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Sets id.
     *
     * @param $id
     * @return $this
     * @internal param string $id
     */
    public function setId($id);

    /**
     * Gets customer id.
     *
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Sets customer id.
     *
     * @param string $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Gets customer.
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer();

    /**
     * Gets billing frequency id.
     *
     * @return string|null
     */
    public function getBillingFrequencyId();

    /**
     * Sets billing frequency id.
     *
     * @param string $billingFrequencyId
     * @return $this
     */
    public function setBillingFrequencyId($billingFrequencyId);

    /**
     * Gets label.
     *
     * @return string|null
     */
    public function getLabel();

    /**
     * Gets unit.
     *
     * @return string|null
     */
    public function getUnit();

    /**
     * Sets unit.
     *
     * @param string $unit
     * @return $this
     */
    public function setUnit($unit);

    /**
     * Gets website id.
     *
     * @return string|null
     */
    public function getWebsiteId();

    /**
     * Sets website id.
     *
     * @param string $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId);

    /**
     * Gets website.
     *
     * @return \Magento\Store\Model\Website
     */
    public function getWebsite();

    /**
     * Gets status.
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Sets status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Gets frequency.
     *
     * @return string|null
     */
    public function getFrequency();

    /**
     * Sets frequency.
     *
     * @param string $frequency
     * @return $this
     */
    public function setFrequency($frequency);

    /**
     * Gets engine.
     *
     * @return string|null
     */
    public function getEngineCode();

    /**
     * Sets engine code.
     *
     * @param string $engineCode
     * @return $this
     */
    public function setEngineCode($engineCode);

    /**
     * Gets subscription profile addresses.
     *
     * @return SubscriptionProfileAddressInterface[]
     */
    public function getAddresses();

    /**
     * Sets subscription profile addresses.
     *
     * @param SubscriptionProfileAddressInterface[] $addresses
     * @return $this
     */
    public function setAddresses($addresses);

    /**
     * Gets subscription profile products.
     *
     * @return ProductSubscriptionProfileInterface[]
     */
    public function getProducts();

    /**
     * Sets subscription profile products.
     *
     * @param ProductSubscriptionProfileInterface[] $products
     * @return $this
     */
    public function setProducts($products);

    /**
     * Gets subscription profile visible products.
     *
     * @return ProductSubscriptionProfileInterface[]
     */
    public function getVisibleProducts();

    /**
     * Sets subscription profile visible products.
     *
     * @param ProductSubscriptionProfileInterface[] $products
     * @return $this
     */
    public function setVisibleProducts($products);

    /**
     * Gets subscription profile shipping address.
     *
     * @return SubscriptionProfileAddressInterface
     */
    public function getShippingAddress();

    /**
     * Gets subscription profile billing address.
     *
     * @return SubscriptionProfileAddressInterface
     */
    public function getBillingAddress();

    /**
     * Gets start date.
     *
     * @return string|null
     */
    public function getStartDate();

    /**
     * Sets start date.
     *
     * @param string $startDate
     * @return $this
     */
    public function setStartDate($startDate);

    /**
     * Gets trial start date.
     *
     * @return string|null
     */
    public function getTrialStartDate();

    /**
     * Sets trial start date.
     *
     * @param string $trialStartDate
     * @return $this
     */
    public function setTrialStartDate($trialStartDate);

    /**
     * Gets term.
     *
     * @return string|null
     */
    public function getTerm();

    /**
     * Sets term.
     *
     * @param string $term
     * @return $this
     */
    public function setTerm($term);

    /**
     * Gets total billing cycles.
     *
     * @return int|null
     */
    public function getTotalBillingCycles();

    /**
     * Sets total billing cycles.
     *
     * @param int $totalBillingCycles
     * @return $this
     */
    public function setTotalBillingCycles($totalBillingCycles);

    /**
     * Gets shipping method.
     *
     * @return string|null
     */
    public function getShippingMethod();

    /**
     * Sets shipping method.
     *
     * @param string $shippingMethod
     * @return $this
     */
    public function setShippingMethod($shippingMethod);

    /**
     * Gets shipping description.
     *
     * @return string|null
     */
    public function getShippingDescription();

    /**
     * Sets shipping description.
     *
     * @param string $shippingDescription
     * @return $this
     */
    public function setShippingDescription($shippingDescription);

    /**
     * Gets profile currency code.
     *
     * @return string|null
     */
    public function getProfileCurrencyCode();

    /**
     * Sets profile currency code.
     *
     * @param string $profileCurrencyCode
     * @return $this
     */
    public function setProfileCurrencyCode($profileCurrencyCode);

    /**
     * Gets  trial length.
     *
     * @return string|null
     */
    public function getTrialLength();

    /**
     * Sets trial length.
     *
     * @param string $trialLength
     * @return $this
     */
    public function setTrialLength($trialLength);

    /**
     * Gets  trial length unit.
     *
     * @return string|null
     */
    public function getTrialLengthUnit();

    /**
     * Sets trial length unit.
     *
     * @param string $trialLengthUnit
     * @return $this
     */
    public function setTrialLengthUnit($trialLengthUnit);

    /**
     * Gets is virtual flag.
     *
     * @return string|null
     */
    public function getIsVirtual();

    /**
     * Sets is virtual flag.
     *
     * @param bool $isVirtual
     * @return $this
     */
    public function setIsVirtual($isVirtual);


    /**
     * Gets payment token hash.
     *
     * @return string|null
     */
    public function getTokenHash();

    /**
     * Sets payment token hash.
     *
     * @param string $tokenHash
     * @return $this
     */
    public function setTokenHash($tokenHash);

    /**
     * Gets payment token.
     *
     * @return string|null
     */
    public function getPaymentToken();

    /**
     * Sets payment token.
     *
     * @param string $token
     * @return $this
     */
    public function setPaymentToken($token);

    /**
     * Gets payment additional info.
     *
     * @return []|null
     */
    public function getDecodedPaymentAdditionalInfo();

    /**
     * Sets payment additional info.
     *
     * @param  [] $info
     * @return $this
     */
    public function setEncodedPaymentAdditionalInfo($info);

    /**
     * Gets payment additional info.
     *
     * @return string|null
     */
    public function getPaymentAdditionalInfo();

    /**
     * Sets payment additional info.
     *
     * @param  string $info
     * @return $this
     */
    public function setPaymentAdditionalInfo($info);

    /**
     * Gets quotes generation state.
     *
     * @return []|null
     */
    public function getGenerateQuotesState();

    /**
     * Sets quotes generation state.
     *
     * @param int $state
     * @return $this
     */
    public function setGenerateQuotesState($state);

    /**
     * Gets created at date.
     *
     * @return []|null
     */
    public function getCreatedAt();

    /**
     * Sets created at date.
     *
     * @param bool $date
     * @return $this
     */
    public function setCreatedAt($date);

    /**
     * Gets updated at date.
     *
     * @return []|null
     */
    public function getUpdatedAt();

    /**
     * Sets updated at date.
     *
     * @param bool $date
     * @return $this
     */
    public function setUpdatedAt($date);

    /**
     * Gets need recollect
     *
     * @return []|null
     */
    public function getNeedRecollect();

    /**
     * Sets need recollect
     *
     * @param bool $needRecollect
     * @return $this
     */
    public function setNeedRecollect($needRecollect);

    /**
     * Gets cancel before next cycle.
     *
     * @return bool
     */
    public function getCancelBeforeNextCycle();

    /**
     * Sets cancel before next cycle.
     *
     * @param bool $cancelBeforeNextCycle
     * @return $this
     */
    public function setCancelBeforeNextCycle($cancelBeforeNextCycle);
}
