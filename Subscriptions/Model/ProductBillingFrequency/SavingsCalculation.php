<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Catalog\Api\Data\ProductInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Class for retrieving product savings calculation type.
 */
class SavingsCalculation
{
    /**
     * Savings calculation type constants.
     */
    const TYPE_DEFAULT = 0;
    const TYPE_PRESET_QTY = 1;
    const TYPE_SERVICE = 2;

    /**
     * Returns product savings calculation type.
     *
     * @param ProductInterface $product
     * @return int
     */
    public function getSavingsCalculationType(ProductInterface $product)
    {
        $result = self::TYPE_DEFAULT;

        if ($product && (int)$product->getData(Attribute::SUBSCRIPTION_SAVINGS_CALCULATION)) {
            $result = self::TYPE_SERVICE;
        } elseif ($product && (int)$product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY)) {
            $result = self::TYPE_PRESET_QTY;
        }

        return $result;
    }
}
