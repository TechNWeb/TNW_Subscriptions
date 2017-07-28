<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\Model\AbstractModel;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile as Resource;

/**
 * Product subscription profile model.
 */
class ProductSubscriptionProfile extends AbstractModel
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
    const DEFAULT_GROUP_CODE = 'additional-information';

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionProfileId()
    {
        return $this->getData(self::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setSubscriptionProfileId($subscriptionProfileId)
    {
        return $this->setData(self::SUBSCRIPTION_PROFILE_ID, $subscriptionProfileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getMagentoProductId()
    {
        return $this->getData(self::MAGENTO_PRODUCT_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setMagentoProductId($magentoProductId)
    {
        return $this->setData(self::MAGENTO_PRODUCT_ID, $magentoProductId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice()
    {
        return $this->getData(self::PRICE);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * {@inheritdoc}
     */
    public function getInitialFee()
    {
        return $this->getData(self::INITIAL_FEE);
    }

    /**
     * {@inheritdoc}
     */
    public function setInitialFee($initialFee)
    {
        return $this->setData(self::INITIAL_FEE, $initialFee);
    }

    /**
     * {@inheritdoc}
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * {@inheritdoc}
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseType()
    {
        return $this->getData(self::PURCHASE_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setPurchaseType($purchaseType)
    {
        return $this->setData(self::PURCHASE_TYPE, $purchaseType);
    }

    /**
     * {@inheritdoc}
     */
    public function getTrialStatus()
    {
        return $this->getData(self::TRIAL_STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setTrialStatus($trialStatus)
    {
        return $this->setData(self::TRIAL_STATUS, $trialStatus);
    }

    /**
     * {@inheritdoc}
     */
    public function getTrialPrice()
    {
        return $this->getData(self::TRIAL_PRICE);
    }

    /**
     * {@inheritdoc}
     */
    public function setTrialPrice($trialPrice)
    {
        return $this->setData(self::TRIAL_PRICE, $trialPrice);
    }

    /**
     * {@inheritdoc}
     */
    public function getLockProductPriceStatus()
    {
        return $this->getData(self::LOCK_PRODUCT_PRICE_STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setLockProductPriceStatus($lockProductPriceStatus)
    {
        return $this->setData(self::LOCK_PRODUCT_PRICE_STATUS, $lockProductPriceStatus);
    }

    /**
     * {@inheritdoc}
     */
    public function getOfferFlatDiscountStatus()
    {
        return $this->getData(self::OFFER_FLAT_DISCOUNT_STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setOfferFlatDiscountStatus($offerFlatDiscountStatus)
    {
        return $this->setData(self::OFFER_FLAT_DISCOUNT_STATUS, $offerFlatDiscountStatus);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountAmount()
    {
        return $this->getData(self::DISCOUNT_AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setDiscountAmount($discountAmount)
    {
        return $this->setData(self::DISCOUNT_AMOUNT, $discountAmount);
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountType()
    {
        return $this->getData(self::DISCOUNT_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setDiscountType($discountType)
    {
        return $this->setData(self::DISCOUNT_TYPE, $discountType);
    }
}
