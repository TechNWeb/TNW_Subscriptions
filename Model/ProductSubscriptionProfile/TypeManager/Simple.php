<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Pricing\SaleableInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;

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
                $buyRequestValue = $this->serializer->unserialize($request->getValue());
                $subscriptionPart = $buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::UNIQUE];
                $subscriptionPart['qty'] = $buyRequestValue['qty'];

                if (isset($buyRequestValue['admin_modification'])
                    && array_key_exists('price', $buyRequestValue)
                ) {
                    $subscriptionPart['price'] = $buyRequestValue['price'];
                }

                if (isset($buyRequestValue['rebill_processing'])
                    && $buyRequestValue['rebill_processing']
                    && isset($buyRequestValue['subscription_data']['unique']['use_preset_qty'])
                    && $buyRequestValue['subscription_data']['unique']['use_preset_qty']
                ) {
                    $updateNonUnique = [
                        'price' => $buyRequestValue['custom_price'],
                        'current_preset_qty_price' => $buyRequestValue['custom_price'],
                        'preset_qty_price' => $buyRequestValue['custom_price'],
                    ];
                } else {
                    $updateNonUnique = [
                        'price' => $this->getSubscriptionPrice($product, $subscriptionPart),
                        'current_preset_qty_price' => $this
                            ->getSubscriptionCurrentPresetQtyPrice($product, $subscriptionPart),
                        'preset_qty_price' => $this->getSubscriptionPresetQtyPrice($product, $subscriptionPart),
                    ];
                }
                $originalNonUnique =
                    isset($buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::NON_UNIQUE])
                        ? $buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::NON_UNIQUE]
                        : [];

                $buyRequestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::NON_UNIQUE] = array_merge(
                    $originalNonUnique,
                    $updateNonUnique
                );
                unset($buyRequestValue['admin_modification']);
                $request->setValue($this->serializer->serialize($buyRequestValue));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getProductDataObject(SaleableInterface $product, array $arguments = null)
    {
        $productData = parent::getProductDataObject($product, $arguments);
        $productId = $product->getId();
        $data = [
            'child_product_id' => $productId,
            'child_product_price' => $this->productRepository->getById($productId)->getFinalPrice(),
        ];
        $productData->addData($data);

        return $productData;
    }
}
