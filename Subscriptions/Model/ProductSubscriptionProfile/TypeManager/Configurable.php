<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\Quote\Item\OptionValueResolver;

/**
 * Configurable product manager.
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
                $requestValue = OptionValueResolver::getDecodedValue($request->getValue());
                $subscriptionPart = $requestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::UNIQUE];
                $subscriptionPart['qty'] = $requestValue['qty'];
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
                                'current_preset_qty_price' => $this->getSubscriptionCurrentPresetQtyPrice($mainProduct, $subscriptionPart),
                                'preset_qty_price' => $this->getSubscriptionPresetQtyPrice($mainProduct, $subscriptionPart),
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
    public function getProductDataObject(SaleableInterface $product, array $arguments = null)
    {
        $productData = parent::getProductDataObject($product, $arguments);
        $childProduct = $product;

        if (!empty($arguments['child_product'])) {
            $childProduct = $arguments['child_product'];
        } elseif (!empty($arguments['super_attribute'])) {
            $superAttributes = $arguments['super_attribute'];

            if ($superAttributes) {
                $existFrequency = false;
                $childProduct = $product->getTypeInstance()->getProductByAttributes($superAttributes, $product);
                if ($childProduct && !empty($arguments['billing_frequency'])) {
                    $existFrequency = $this->checkFrequencyExistanse(
                        $arguments['billing_frequency'],
                        [$childProduct->getId()]
                    );
                }
                if (!$existFrequency) {
                    $childProduct = $product;
                }
            }
        }

        if ($childProduct) {
            $data = [
                'child_product_id' => $childProduct->getId(),
                'child_product_price' => $childProduct->getOrigData('price'),
            ];
        }

        $productData->addData($data);

        return $productData;
    }

    /**
     * @inheritdoc
     */
    public function checkFrequencyExistanse($billingFrequency, array $productIds)
    {
        $this->searchCriteriaBuilder->addFilter(
            ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID,
            $billingFrequency
        )->addFilter(
            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
            $productIds,
            'in'
        );
        /** @var \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $relationsCount = $this->productFrequencyRepository->getList($searchCriteria)->getTotalCount();

        return 0 !== $relationsCount;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData(CartItemInterface $item)
    {
       return [
           'super_attribute' => $item->getBuyRequest()->getSuperAttribute(),
       ];
    }
}
