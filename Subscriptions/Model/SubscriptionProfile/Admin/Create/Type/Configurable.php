<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\Quote\Item\OptionValueResolver;

/**
 * Buy request modifier for configurable products.
 */
class Configurable extends Base
{
    /**
     * @inheritdoc
     */
    public function modifyBuyRequests(array $products)
    {
        list($mainProduct, $childProduct) = $products;
        if ($mainProduct && $childProduct) {
            /** @var ProductInterface $mainProduct */
            $request = $mainProduct->getCustomOption('info_buyRequest');
            if ($request) {
                $valueFormat = OptionValueResolver::getValueFormat($request->getValue());
                $requestValue = OptionValueResolver::decode($request->getValue());
                $subscriptionPart = $requestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::UNIQUE];
                if (!empty($requestValue['super_attribute'])) {
                    $subscriptionPart['super_attribute'] = $requestValue['super_attribute'];
                } else {
                    throw new LocalizedException(__('Super attributes must be set'));
                }

                $requestValue = array_merge_recursive(
                    $requestValue,
                    [
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                            Create::NON_UNIQUE => [
                                'price' => $this->getSubscriptionPrice(
                                    $mainProduct,
                                    $subscriptionPart
                                ),
                            ],
                        ],
                    ]
                );
                $request->setValue(OptionValueResolver::getEncodedValue($requestValue, $valueFormat));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionCustomPrice(ProductInterface $product, array $productData)
    {
        return $this->getCalculatedPrice($product, $productData, true);
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPrice(ProductInterface $product, array $productData)
    {
        return $this->getCalculatedPrice($product, $productData);
    }
}
