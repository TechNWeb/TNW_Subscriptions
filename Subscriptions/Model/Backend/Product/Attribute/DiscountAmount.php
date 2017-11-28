<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Backend\Product\Attribute;

use TNW\Subscriptions\Model\Product\Attribute;

/**
 * tnw_subscr_discount_amount attribute backend model.
 */
class DiscountAmount extends \Magento\Catalog\Model\Product\Attribute\Backend\Price
{
    const FLAT_FEE_DISCOUNT = 1;
    const PERCENT_DISCOUNT = 2;

    /**
     * Before save method
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    public function beforeSave($object)
    {
        $attrCode = $this->getAttribute()->getAttributeCode();
        if ($object->hasData($attrCode) && $object->getData($attrCode)) {
            $validated = $this->checkDiscountLessProductPrice($object, $attrCode);
            if (!$validated) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The discount cannot exceed the total product cost.')
                );
            }

        }

        return parent::beforeSave($object);
    }

    /**
     * Checks if price with discount less then product price.
     *
     * @param \Magento\Catalog\Model\Product $object
     * @param string $attrCode
     * @return bool
     */
    private function checkDiscountLessProductPrice($object, $attrCode)
    {
        $offerFlatDiscount =
            $object->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE) !=
                \TNW\Subscriptions\Model\Config\Source\PurchaseType::ONE_TIME_PURCHASE_TYPE
            && $object->getData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE)
            && $object->getData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT);

        if ($offerFlatDiscount) {
            $valueWithDiscount = 0;
            $discountAmountValue = $this->localeFormat->getNumber($object->getData($attrCode));
            $discountAmountType = $object->getData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE);
            $productPrice = $this->localeFormat->getNumber($object->getPrice());

            if ($discountAmountType == self::FLAT_FEE_DISCOUNT) {
                $valueWithDiscount = $productPrice - $discountAmountValue;
            } elseif ($discountAmountType == self::PERCENT_DISCOUNT) {
                $valueWithDiscount = $productPrice * (100 - $discountAmountValue) / 100;
            }

            return $valueWithDiscount >= 0;
        }

        return true;
    }
}
