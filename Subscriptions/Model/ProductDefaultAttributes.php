<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

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
            'tnw_subscr_purchase_type' => $this->config->purchaseType($websiteId),
            'tnw_subscr_start_date' => $this->config->startDateType($websiteId),
            'tnw_subscr_lock_product_price' => $this->config->lockProductPriceStatus($websiteId) ? '1' : '0',
            // Discount
            'tnw_subscr_offer_flat_discount' => $this->config->offerFlatDiscountStatus($websiteId) ? '1' : '0',
            'tnw_subscr_discount_amount' => $this->config->discountAmount($websiteId),
            'tnw_subscr_discount_type' => $this->config->discountType($websiteId),
            // Trial
            'tnw_subscr_trial_status' => $this->config->trialStatus($websiteId) ? '1' : '0',
            'tnw_subscr_trial_length' => $this->config->trialLength($websiteId),
            'tnw_subscr_trial_length_unit' => $this->config->trialLengthUnit($websiteId),
            'tnw_subscr_trial_price' => $this->config->trialPrice($websiteId),
            'tnw_subscr_trial_start_date' => $this->config->trialStartDateType($websiteId),
        ];

        return $dataDefault;
    }
}
