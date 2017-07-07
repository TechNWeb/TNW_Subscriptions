<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;

/**
 * Product subscription profile model.
 */
class ProductSubscriptionProfile extends \Magento\Framework\Model\AbstractModel
    implements ProductSubscriptionProfileInterface
{
    /**
     * Entity code.
     */
    const ENTITY = 'tnw_product_subscription_profile';

    /**
     * Entity table.
     */
    const ENTITY_TABLE = 'tnw_subscriptions_product_subscription_profile_entity';

    /*
     * Default group code for custom attributes.
     */
    const DEFAUL_GROUP_CODE = 'additional-information';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile::class);
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
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get subscription_profile_id
     * @return string
     */
    public function getSubscriptionProfileId()
    {
        return $this->getData(self::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * Set subscription_profile_id
     * @param string $subscription_profile_id
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setSubscriptionProfileId($subscription_profile_id)
    {
        return $this->setData(self::SUBSCRIPTION_PROFILE_ID, $subscription_profile_id);
    }

    /**
     * Get magento_product_id
     * @return string
     */
    public function getMagentoProductId()
    {
        return $this->getData(self::MAGENTO_PRODUCT_ID);
    }

    /**
     * Set magento_product_id
     * @param string $magento_product_id
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setMagentoProductId($magento_product_id)
    {
        return $this->setData(self::MAGENTO_PRODUCT_ID, $magento_product_id);
    }

    /**
     * Get price
     * @return string
     */
    public function getPrice()
    {
        return $this->getData(self::PRICE);
    }

    /**
     * Set price
     * @param string $price
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * Get initial_fee
     * @return string
     */
    public function getInitialFee()
    {
        return $this->getData(self::INITIAL_FEE);
    }

    /**
     * Set initial_fee
     * @param string $initial_fee
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setInitialFee($initial_fee)
    {
        return $this->setData(self::INITIAL_FEE, $initial_fee);
    }

    /**
     * Get qty
     * @return string
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * Set qty
     * @param string $qty
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * Get purchase_type
     * @return string
     */
    public function getPurchaseType()
    {
        return $this->getData(self::PURCHASE_TYPE);
    }

    /**
     * Set purchase_type
     * @param string $purchase_type
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setPurchaseType($purchase_type)
    {
        return $this->setData(self::PURCHASE_TYPE, $purchase_type);
    }

    /**
     * Get trial_status
     * @return string
     */
    public function getTrialStatus()
    {
        return $this->getData(self::TRIAL_STATUS);
    }

    /**
     * Set trial_status
     * @param string $trial_status
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setTrialStatus($trial_status)
    {
        return $this->setData(self::TRIAL_STATUS, $trial_status);
    }

    /**
     * Get trial_length
     * @return string
     */
    public function getTrialLength()
    {
        return $this->getData(self::TRIAL_LENGTH);
    }

    /**
     * Set trial_length
     * @param string $trial_length
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setTrialLength($trial_length)
    {
        return $this->setData(self::TRIAL_LENGTH, $trial_length);
    }

    /**
     * Get trial_length_unit
     * @return string
     */
    public function getTrialLengthUnit()
    {
        return $this->getData(self::TRIAL_LENGTH_UNIT);
    }

    /**
     * Set trial_length_unit
     * @param string $trial_length_unit
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setTrialLengthUnit($trial_length_unit)
    {
        return $this->setData(self::TRIAL_LENGTH_UNIT, $trial_length_unit);
    }

    /**
     * Get trial_price
     * @return string
     */
    public function getTrialPrice()
    {
        return $this->getData(self::TRIAL_PRICE);
    }

    /**
     * Set trial_price
     * @param string $trial_price
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setTrialPrice($trial_price)
    {
        return $this->setData(self::TRIAL_PRICE, $trial_price);
    }

    /**
     * Get start_date
     * @return string
     */
    public function getStartDate()
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * Set start_date
     * @param string $start_date
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setStartDate($start_date)
    {
        return $this->setData(self::START_DATE, $start_date);
    }

    /**
     * Get lock_product_price_status
     * @return string
     */
    public function getLockProductPriceStatus()
    {
        return $this->getData(self::LOCK_PRODUCT_PRICE_STATUS);
    }

    /**
     * Set lock_product_price_status
     * @param string $lock_product_price_status
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setLockProductPriceStatus($lock_product_price_status)
    {
        return $this->setData(self::LOCK_PRODUCT_PRICE_STATUS, $lock_product_price_status);
    }

    /**
     * Get offer_flat_discount_status
     * @return string
     */
    public function getOfferFlatDiscountStatus()
    {
        return $this->getData(self::OFFER_FLAT_DISCOUNT_STATUS);
    }

    /**
     * Set offer_flat_discount_status
     * @param string $offer_flat_discount_status
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setOfferFlatDiscountStatus($offer_flat_discount_status)
    {
        return $this->setData(self::OFFER_FLAT_DISCOUNT_STATUS, $offer_flat_discount_status);
    }

    /**
     * Get discount_amount
     * @return string
     */
    public function getDiscountAmount()
    {
        return $this->getData(self::DISCOUNT_AMOUNT);
    }

    /**
     * Set discount_amount
     * @param string $discount_amount
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setDiscountAmount($discount_amount)
    {
        return $this->setData(self::DISCOUNT_AMOUNT, $discount_amount);
    }

    /**
     * Get discount_type
     * @return string
     */
    public function getDiscountType()
    {
        return $this->getData(self::DISCOUNT_TYPE);
    }

    /**
     * Set discount_type
     * @param string $discount_type
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     */
    public function setDiscountType($discount_type)
    {
        return $this->setData(self::DISCOUNT_TYPE, $discount_type);
    }
}
