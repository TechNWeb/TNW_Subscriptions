<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

use Magento\Catalog\Model\Product;

interface ProductSubscriptionProfileInterface
{
    /**#@+
     * Constants for field names
     */
    const ID = 'entity_id';
    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const MAGENTO_PRODUCT_ID = 'magento_product_id';
    const PRICE = 'price';
    const INITIAL_FEE = 'initial_fee';
    const QTY = 'qty';
    const PURCHASE_TYPE = 'purchase_type';
    const TRIAL_STATUS = 'trial_status';
    const TRIAL_PRICE = 'trial_price';
    const LOCK_PRODUCT_PRICE_STATUS = 'lock_product_price_status';
    const OFFER_FLAT_DISCOUNT_STATUS = 'offer_flat_discount_status';
    const DISCOUNT_AMOUNT = 'discount_amount';
    const DISCOUNT_TYPE = 'discount_type';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**#@+
     * Constant for profile magento product
     */
    const MAGENTO_PRODUCT = 'magento_product';
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
     * Sets subscription profile id.
     *
     * @param string $subscriptionProfileId
     * @return $this
     */
    public function setSubscriptionProfileId($subscriptionProfileId);

    /**
     * Gets magento product id.
     *
     * @return string|null
     */
    public function getMagentoProductId();

    /**
     * Sets magento product id.
     *
     * @param string $magentoProductId
     * @return $this
     */
    public function setMagentoProductId($magentoProductId);

    /**
     * Gets magento product.
     *
     * @return Product|null
     */
    public function getMagentoProduct();

    /**
     * Gets price.
     *
     * @return string|null
     */
    public function getPrice();

    /**
     * Sets price.
     *
     * @param string $price
     * @return $this
     */
    public function setPrice($price);

    /**
     * Gets initial fee.
     *
     * @return string|null
     */
    public function getInitialFee();

    /**
     * Sets initial fee.
     *
     * @param string $initialFee
     * @return $this
     */
    public function setInitialFee($initialFee);

    /**
     * Gets qty.
     *
     * @return string|null
     */
    public function getQty();

    /**
     * Sets qty.
     *
     * @param string $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * Gets purchase type.
     *
     * @return string|null
     */
    public function getPurchaseType();

    /**
     * Sets purchase type.
     *
     * @param $purchaseType
     * @return string
     */
    public function setPurchaseType($purchaseType);

    /**
     * Gets trial status.
     *
     * @return string
     */
    public function getTrialStatus();

    /**
     * Sets trial status.
     *
     * @param string $trialStatus
     * @return $this
     */
    public function setTrialStatus($trialStatus);

    /**
     * Gets trial price.
     *
     * @return string
     */
    public function getTrialPrice();

    /**
     * Sets trial price.
     *
     * @param string $trialPrice
     * @return $this
     */
    public function setTrialPrice($trialPrice);

    /**
     * Gets lock product price status.
     *
     * @return string
     */
    public function getLockProductPriceStatus();

    /**
     * Sets lock product price status.
     *
     * @param string $lockProductPriceStatus
     * @return $this
     */
    public function setLockProductPriceStatus($lockProductPriceStatus);

    /**
     * Gets offer flat discount status.
     *
     * @return string
     */
    public function getOfferFlatDiscountStatus();

    /**
     * Sets offer flat discount status.
     *
     * @param string $offerFlatDiscountStatus
     * @return $this
     */
    public function setOfferFlatDiscountStatus($offerFlatDiscountStatus);

    /**
     * Gets discount amount.
     *
     * @return string
     */
    public function getDiscountAmount();

    /**
     * Sets discount amount.
     *
     * @param string $discountAmount
     * @return $this
     */
    public function setDiscountAmount($discountAmount);

    /**
     * Gets discount type.
     *
     * @return string
     */
    public function getDiscountType();

    /**
     * Sets discount type.
     *
     * @param $discountType
     * @return $this
     */
    public function setDiscountType($discountType);

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
}
