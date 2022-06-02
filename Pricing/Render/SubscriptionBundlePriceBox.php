<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Pricing\Render;

use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\CustomOptionPrice;
use Magento\Framework\DataObject;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Class for subscription price rendering for bundle products.
 */
class SubscriptionBundlePriceBox extends SubscriptionPriceBox
{
    /**
     * @inheritdoc
     */
    public function getProductBillingFrequencies(DataObject $product)
    {
        $result = parent::getProductBillingFrequencies($product);

        foreach ($result as &$frequency) {
            $frequency['old_price'] = $product->getFinalMinimalPrice();
        }

        return $result;
    }

    /**
     * Check if bundle product has one or more options, or custom options, with different prices
     *
     * @return bool
     */
    public function showRangePrice()
    {
        /** @var FinalPrice $bundlePrice */
        $bundlePrice = $this->getPriceType(FinalPrice::PRICE_CODE);
        $showRange = $bundlePrice->getMinimalPrice() != $bundlePrice->getMaximalPrice();

        if (!$showRange) {
            //Check the custom options, if any
            /** @var \Magento\Catalog\Pricing\Price\CustomOptionPrice $customOptionPrice */
            $customOptionPrice = $this->getPriceType(CustomOptionPrice::PRICE_CODE);
            $showRange =
                $customOptionPrice->getCustomOptionRange(true) != $customOptionPrice->getCustomOptionRange(false);
        }

        return $showRange;
    }

    /**
     * Get minimum price for options
     * @return float
     */
    public function getMinimalOptionPrice()
    {
        $bundlePrice = $this->getPriceType(FinalPrice::PRICE_CODE);
        return $this->applyFlatDiscount(
            $bundlePrice->getMinimalPrice()->getValue() - $bundlePrice->getPriceWithoutOption()->getValue()
        );
    }

    /**
     * Get maximum price for options
     * @return float
     */
    public function getMaximalOptionPrice()
    {
        $bundlePrice = $this->getPriceType(FinalPrice::PRICE_CODE);
        return $this->applyFlatDiscount(
            $bundlePrice->getMaximalPrice()->getValue() - $bundlePrice->getPriceWithoutOption()->getValue()
        );
    }

    /**
     * Apply flat discount, if applicable
     * @param $amount
     * @return float
     */
    private function applyFlatDiscount($amount)
    {
        $bundleProduct = $this->getProduct();

        if ($bundleProduct->getData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE) === '1'
            && $bundleProduct->getData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT) === '1'
            && $bundleProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE) === '2'
        ) {
            $amount *= (100 - (float)$bundleProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT))/100;
        }
        return $amount;
    }
}
