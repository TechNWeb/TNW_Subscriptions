<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductSubscriptionProfile\TypeManager;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as ProductFrequencyRepository;
use TNW\Subscriptions\Model\Product\Attribute as SubscriptionProductAttributes;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;

/**
 * Base class for product manager by type.
 */
abstract class Base implements TypeInterface
{
    /**
     * @var PriceCalculator
     */
    protected $priceCalculator;

    /**
     * @var ProductFrequencyRepository
     */
    protected $productFrequencyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param PriceCalculator $priceCalculator
     * @param ProductFrequencyRepository $productFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        PriceCalculator $priceCalculator,
        ProductFrequencyRepository $productFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        $this->priceCalculator = $priceCalculator;
        $this->productFrequencyRepository = $productFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionCustomPrice(ProductInterface $product, array $productData)
    {
        $productObjectData = $this->getProductDataObject($product, $productData);

        return $this->getCalculatedPrice($productObjectData, $productData, true);
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPrice(ProductInterface $product, array $productData)
    {
        $productObjectData = $this->getProductDataObject($product, $productData);

        return $this->getCalculatedPrice($productObjectData, $productData);
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionCurrentPresetQtyPrice(ProductInterface $product, array $productData)
    {
        $productObjectData = $this->getProductDataObject($product, $productData);

        return $this->getCalculatedPrice(
            $productObjectData,
            $productData,
            true,
            true
        );
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPresetQtyPrice(ProductInterface $product, array $productData)
    {
        $productObjectData = $this->getProductDataObject($product, $productData);

        return $this->getCalculatedPrice(
            $productObjectData,
            $productData,
            false,
            true
        );
    }

    /**
     * @inheritdoc
     */
    public function getProductDataObject(SaleableInterface $product, array $arguments = null)
    {
        $data = [
            'price' => $product->getOrigData('price'),
            'id' => $product->getId(),
            'type_id' => $product->getTypeId(),
            SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_STATUS),
            SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_PRICE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_PRICE),
            SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_LENGTH =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_LENGTH),
            SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_LENGTH_UNIT =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_LENGTH_UNIT),
            SubscriptionProductAttributes::SUBSCRIPTION_LOCK_PRODUCT_PRICE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_LOCK_PRODUCT_PRICE),
            SubscriptionProductAttributes::SUBSCRIPTION_OFFER_FLAT_DISCOUNT =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_OFFER_FLAT_DISCOUNT),
            SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_TYPE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_TYPE),
            SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_AMOUNT =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_AMOUNT),
            SubscriptionProductAttributes::SUBSCRIPTION_UNLOCK_PRESET_QTY =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_UNLOCK_PRESET_QTY),
            SubscriptionProductAttributes::SUBSCRIPTION_SAVINGS_CALCULATION =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_SAVINGS_CALCULATION),
            SubscriptionProductAttributes::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS),
        ];
        $productData = new DataObject();
        $productData->addData($data);

        return $productData;
    }

    /**
     * Returns calculated product price.
     *
     * @param DataObject $product
     * @param array $productData
     * @param bool $full
     * @return float|string
     */
    protected function getCalculatedPrice(
        DataObject $product,
        array $productData,
        $full = false,
        $rowPrice = false
    ) {
        $productQty  = !empty($productData['qty']) ? $productData['qty'] : 0;
            $usePresetQty = !empty($productData['use_preset_qty']) && $productQty;
        //Calculate product Price
        $price = $this->priceCalculator->getUnitPrice(
            $product,
            $productData['billing_frequency'],
            isset($productData['price']) ? $productData['price'] : null,
            $full
        );

        if (!$rowPrice && $usePresetQty) {
            $price = $productQty ? round($price / $productQty, 4) : 0;
        } elseif ($rowPrice && !$usePresetQty) {
            $price *= $productData['qty'];
        }

        return $price;
    }

    /**
     * @inheritdoc
     */
    public function checkFrequencyExistanse($billingFrequency, $productId)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData(CartItemInterface $item)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getDiscountFields(SaleableInterface $product)
    {
        $data = [
            SubscriptionProductAttributes::SUBSCRIPTION_OFFER_FLAT_DISCOUNT =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_OFFER_FLAT_DISCOUNT),
            SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_TYPE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_TYPE),
            SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_AMOUNT =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_DISCOUNT_AMOUNT),
            SubscriptionProductAttributes::SUBSCRIPTION_LOCK_PRODUCT_PRICE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_LOCK_PRODUCT_PRICE),
        ];

        return $data;
    }
}
