<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;

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
                $requestValue = unserialize($request->getValue());
                $subscriptionPart = $requestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::UNIQUE];
                if (!empty($requestValue['super_attribute'])){
                    $subscriptionPart['super_attribute'] =  $requestValue['super_attribute'];
                } else {
                    throw new \Exception(__('Super attributes must be set'));
                }

                $frequency = $subscriptionPart['billing_frequency'];
                $this->checkFrequencyExistanse(
                    $frequency,
                    [$mainProduct->getId(), $childProduct->getId()]
                );
                $requestValue = array_merge_recursive(
                    $requestValue,
                    [
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                            Create::NON_UNIQUE => [
                                'price' => $this->getSubscriptionPrice(
                                    $mainProduct,
                                    $subscriptionPart
                                )
                            ],
                        ],
                    ]
                );
                $request->setValue(serialize($requestValue));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionCustomPrice(ProductInterface $product, array $productData)
    {
        $result = 0;
        $superAttributes = !empty($productData['super_attribute']) ? $productData['super_attribute'] : [];
        if ($superAttributes) {
            $childProduct = $product->getTypeInstance()->getProductByAttributes($superAttributes, $product);
            if ($childProduct){
                $result = $this->getCalculatedPrice($childProduct, $productData, true);
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPrice(ProductInterface $product, array $productData)
    {
        $result = 0;
        $superAttributes = !empty($productData['super_attribute']) ? $productData['super_attribute'] : [];
        if ($superAttributes) {
            $childProduct = $product->getTypeInstance()->getProductByAttributes($superAttributes, $product);
            if ($childProduct){
                $result = $this->getCalculatedPrice($childProduct, $productData);
            }
        }

        return $result;
    }
}