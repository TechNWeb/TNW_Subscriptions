<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Model to set product default attributes from configuration.
 */
class ProductDefaultAttributes
{
    /**
     * Configuration wrapper.
     *
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * Set Subscriptions attributes default values from config.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    public function setDefaultValues($product)
    {
        $dataDefault = $this->getDefaultValues($product);

        $product->addData($dataDefault);
    }

    /**
     * Get Subscriptions attributes default values from config.
     *
     * @param \Magento\Catalog\Model\Product|null $product
     * @return array
     */
    public function getDefaultValues($product = null)
    {
        $websiteId = $product ? $product->getStore()->getWebsiteId() : null;
        $dataDefault = [
            // General
            Attribute::SUBSCRIPTION_PURCHASE_TYPE => $this->config->getPurchaseType($websiteId),
            Attribute::SUBSCRIPTION_START_DATE => $this->config->getStartDateType($websiteId),
            Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE => $this->config->getUnlockPresetQtyStatus($websiteId) ? '1' : '0',
            // Discount
            Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT => $this->config->getOfferFlatDiscountStatus($websiteId) ? '1' : '0',
            Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT => $this->config->getDiscountAmount($websiteId),
            Attribute::SUBSCRIPTION_DISCOUNT_TYPE => $this->config->getDiscountType($websiteId),
            // Trial
            Attribute::SUBSCRIPTION_TRIAL_STATUS => $this->config->getTrialStatus($websiteId) ? '1' : '0',
            Attribute::SUBSCRIPTION_TRIAL_LENGTH => $this->config->getTrialLength($websiteId),
            Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT => $this->config->getTrialLengthUnit($websiteId),
            Attribute::SUBSCRIPTION_TRIAL_PRICE => $this->config->getTrialPrice($websiteId),
            Attribute::SUBSCRIPTION_TRIAL_START_DATE => $this->config->getTrialStartDateType($websiteId),
            Attribute::SUBSCRIPTION_SAVINGS_CALCULATION => $this->config->getSavingsCalculation($websiteId),
        ];

        return $dataDefault;
    }
}
