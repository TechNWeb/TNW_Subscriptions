<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as ProductFrequencyRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Service\Serializer;

/**
 * Configurable product manager.
 */
class Configurable extends Base
{
    /**
     * Configurable constructor.
     * @param Config $config
     * @param PriceCalculator $priceCalculator
     * @param ProductFrequencyRepository $productFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param Serializer $serializer
     */
    public function __construct(
        Config $config,
        PriceCalculator $priceCalculator,
        ProductFrequencyRepository $productFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        Serializer $serializer
    ) {
        parent::__construct(
            $config,
            $priceCalculator,
            $productFrequencyRepository,
            $searchCriteriaBuilder,
            $productRepository,
            $serializer
        );
    }

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
                $requestValue = $this->serializer->unserialize($request->getValue());
                $subscriptionPart = $requestValue[Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME][Create::UNIQUE];
                $subscriptionPart['qty'] = $requestValue['qty'];
                if (!empty($requestValue['super_attribute'])) {
                    $subscriptionPart['super_attribute'] = $requestValue['super_attribute'];
                } else {
                    throw new LocalizedException(__('Super attributes must be set'));
                }

                $requestValue = array_replace_recursive(
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
                    ],
                    $requestValue
                );
                $request->setValue($this->serializer->serialize($requestValue));
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
        $discountFields = [];

        if (!empty($arguments['child_product'])) {
            $childProduct = $arguments['child_product'];
            $productData = $this->processInheritance($arguments['child_product'], $product, $productData);
        } elseif (!empty($arguments['super_attribute'])) {
            $superAttributes = $arguments['super_attribute'];

            if ($superAttributes) {
                $existFrequency = false;
                $childProduct = $product->getTypeInstance()->getProductByAttributes($superAttributes, $product);
                if ($childProduct) {
                    $productData = $this->processInheritance($childProduct, $product, $productData);
                }
                if ($childProduct && !empty($arguments['billing_frequency'])) {
                    $existFrequency = $this->checkFrequencyExistanse(
                        $arguments['billing_frequency'],
                        [$childProduct->getId()]
                    );
                }
                if (!$existFrequency) {
                    $childProduct = $product;

                    $discountFields = $this->getDiscountFields($product);

                    if (!$discountFields[Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT]) {
                        $discountFields = $this->getDiscountFields($childProduct);
                    }
                }
            }
        }

        $data = [];
        if ($childProduct) {
            $childProductId = $childProduct->getId();
            $data = [
                'child_product_id' => $childProductId,
                'child_product_price' => $this->productRepository->getById($childProductId)->getFinalPrice(),
            ];
        }

        $productData->addData(array_merge($data, $discountFields));

        return $productData;
    }

    /**
     * @inheritdoc
     */
    public function checkFrequencyExistanse($billingFrequency, $productId)
    {
        $this->searchCriteriaBuilder->addFilter(
            ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID,
            $billingFrequency
        )->addFilter(
            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
            $productId
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

    /**
     * Set attributes to product data object according to inheritance
     * @param $childProduct
     * @param $product
     * @param $productData
     * @return mixed
     */
    public function processInheritance($childProduct, $product, $productData)
    {
        $inheritance = $product->getData(Attribute::SUBSCRIPTION_INHERITANCE);
        if (!empty($inheritance)) {
            $inheritance = $this->serializer->unserialize($inheritance);
            foreach ($inheritance as $key => $attribute) {
                if ((int)$attribute === 1) {
                    $productData->setData($key, $childProduct->getData($key));
                    if ($key === Attribute::SUBSCRIPTION_TRIAL_STATUS) {
                        $trialData = [
                            Attribute::SUBSCRIPTION_TRIAL_LENGTH =>
                                $childProduct->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH),
                            Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT =>
                                $childProduct->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT),
                            Attribute::SUBSCRIPTION_TRIAL_PRICE =>
                                $childProduct->getData(Attribute::SUBSCRIPTION_TRIAL_PRICE),
                            Attribute::SUBSCRIPTION_TRIAL_START_DATE =>
                                $childProduct->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE),
                            Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP =>
                                $childProduct->getData(Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP)
                        ];

                        $productData->addData($trialData);
                    }
                    if ($key === Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT) {
                        $discountData = [
                            Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT =>
                                $childProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT),
                            Attribute::SUBSCRIPTION_DISCOUNT_TYPE =>
                                $childProduct->getData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE),
                        ];

                        $productData->addData($discountData);
                    }
                }
            }
        }
        $productData->setData(
            Attribute::SUBSCRIPTION_PURCHASE_TYPE,
            $childProduct->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE)
        );
        $productData->setData(
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
            $childProduct->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY)
        );
        return $productData;
    }
}
