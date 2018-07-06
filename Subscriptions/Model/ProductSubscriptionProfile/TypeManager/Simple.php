<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Pricing\SaleableInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\Quote\Item\OptionValueResolver;

/**
 * Simple product manager.
 */
class Simple extends Base
{
    /**
     * @inheritdoc
     */
    public function modifyBuyRequests(array $products)
    {
        /** @var ProductInterface $product */
        foreach ($products as $product) {
            $request = $product->getCustomOption('info_buyRequest');
            if ($request) {
                $valueFormat = OptionValueResolver::getValueFormat($request->getValue());
                $buyRequestValue = OptionValueResolver::getDecodedValue($request->getValue());
                $subscriptionPart = $buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::UNIQUE];
                $subscriptionPart['qty'] = $buyRequestValue['qty'];
                $result = array_merge_recursive(
                    $buyRequestValue,
                    [
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                            Create::NON_UNIQUE => [
                                'price' => $this->getSubscriptionPrice($product, $subscriptionPart),
                                'current_preset_qty_price' => $this->getSubscriptionCurrentPresetQtyPrice($product, $subscriptionPart),
                                'preset_qty_price' => $this->getSubscriptionPresetQtyPrice($product, $subscriptionPart),
                            ],
                        ],
                    ]
                );
                $request->setValue(OptionValueResolver::getEncodedValue($result, $valueFormat));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getProductDataObject(SaleableInterface $product, array $arguments = null)
    {
        $productData = parent::getProductDataObject($product, $arguments);

        $data = [
            'child_product_id' => $product->getId(),
            'child_product_price' => $product->getOrigData('price'),
        ];
        $productData->addData($data);

        return $productData;
    }
}
