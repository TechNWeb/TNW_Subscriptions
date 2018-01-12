<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote\Item;

use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Plugin for quote config.
 */
class QuoteConfigProductAttributes
{
    /**
     * Append subscription product attribute keys to select by quote item collection
     *
     * @param \Magento\Quote\Model\Quote\Config $subject
     * @param array $attributeKeys
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProductAttributes(\Magento\Quote\Model\Quote\Config $subject, array $attributeKeys)
    {
        return array_merge(
            $attributeKeys,
            [
                Attribute::SUBSCRIPTION_PURCHASE_TYPE,
                Attribute::SUBSCRIPTION_TRIAL_STATUS,
                Attribute::SUBSCRIPTION_TRIAL_LENGTH,
                Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
                Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
                Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
                Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT,
                Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
                Attribute::SUBSCRIPTION_TRIAL_PRICE,
                Attribute::SUBSCRIPTION_TRIAL_START_DATE,
                Attribute::SUBSCRIPTION_START_DATE,
                Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
                Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
                'short_description',
            ]
        );
    }
}
