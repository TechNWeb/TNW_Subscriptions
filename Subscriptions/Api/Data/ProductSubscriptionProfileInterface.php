<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface ProductSubscriptionProfileInterface
{

    const ID = 'id';
    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const MAGENTO_PRODUCT_ID = 'magento_product_id';
    const PRICE = 'price';
    const INITIAL_FEE = 'initial_fee';
    const QTY = 'qty';
    const PURCHASE_TYPE = 'purchase_type';
    CONST TRIAL_STATUS = 'trial_status';
    CONST TRIAL_LENGTH = 'trial_length';
    CONST TRIAL_LENGTH_UNIT = 'trial_length_unit';
    CONST TRIAL_PRICE = 'trial_price';
    CONST START_DATE = 'start_date';
    CONST LOCK_PRODUCT_PRICE_STATUS = 'lock_product_price_status';
    CONST OFFER_FLAT_DISCOUNT_STATUS = 'offer_flat_discount_status';
    CONST DISCOUNT_AMOUNT = 'discount_amount';
    CONST DISCOUNT_TYPE = 'discount_type';

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param $id
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
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
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setSubscriptionProfileId($subscription_profile_id);

    /**
     * Get magento_product_id
     * @return string|null
     */
    public function getMagentoProductId();

    /**
     * Set magento_product_id
     * @param string $magento_product_id
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setMagentoProductId($magento_product_id);

    /**
     * Get price
     * @return string|null
     */
    public function getPrice();

    /**
     * Set price
     * @param string $price
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setPrice($price);

    /**
     * Get initial_fee
     * @return string|null
     */
    public function getInitialFee();

    /**
     * Set initial_fee
     * @param string $initial_fee
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     */
    public function setInitialFee($initial_fee);

    /**
     * Get qty
     * @return string|null
     */
    public function getQty();

    /**
     * Set
     * @param $qty
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $qty
     */
    public function setQty($qty);

    /**
     * Get purchase_type
     * @return string|null
     */
    public function getPurchaseType();

    /**
     * Set purchase_type
     * @param $purchase_type
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $purchase_type
     */
    public function setPurchaseType($purchase_type);

    /**
     * Get trial_status
     * @return string
     */
    public function getTrialStatus();

    /**
     * Set trial_status
     * @param $trial_status
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $trial_status
     */
    public function setTrialStatus($trial_status);

    /**
     * Get trial_length
     * @return string
     */
    public function getTrialLength();

    /**
     * Set trial_length
     * @param $trial_length
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $trial_length
     */
    public function setTrialLength($trial_length);

    /**
     * Get trial_length_unit
     * @return string
     */
    public function getTrialLengthUnit();

    /**
     * Set trial_length_unit
     * @param $trial_length_unit
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $trial_length_unit
     */
    public function setTrialLengthUnit($trial_length_unit);

    /**
     * Get trial_price
     * @return string
     */
    public function getTrialPrice();

    /**
     * Set trial_price
     * @param $trial_price
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $trial_price
     */
    public function setTrialPrice($trial_price);

    /**
     * Get start_date
     * @return string
     */
    public function getStartDate();

    /**
     * Set start_date
     * @param $start_date
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $start_date
     */
    public function setStartDate($start_date);

    /**
     * Get lock_product_price_status
     * @return string
     */
    public function getLockProductPriceStatus();

    /**
     * Set lock_product_price_status
     * @param $lock_product_price_status
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $lock_product_price_status
     */
    public function setLockProductPriceStatus($lock_product_price_status);

    /**
     * Get offer_flat_discount_status
     * @return string
     */
    public function getOfferFlatDiscountStatus();

    /**
     * Set offer_flat_discount_status
     * @param $offer_flat_discount_status
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $offer_flat_discount_status
     */
    public function setOfferFlatDiscountStatus($offer_flat_discount_status);

    /**
     * Get discount_amount
     * @return string
     */
    public function getDiscountAmount();

    /**
     * Set discount_amount
     * @param $discount_amount
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $discount_amount
     */
    public function setDiscountAmount($discount_amount);

    /**
     * Get discount_type
     * @return string
     */
    public function getDiscountType();

    /**
     * Set discount_type
     * @param $discount_type
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @internal param string $discount_type
     */
    public function setDiscountType($discount_type);

}
