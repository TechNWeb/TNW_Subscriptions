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
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\PriceStrategy;
use TNW\Subscriptions\Service\Serializer;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var null
     */
    private $profileProduct = null;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var null
     */
    protected $profile = null;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * Base constructor.
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
        $this->config = $config;
        $this->priceCalculator = $priceCalculator;
        $this->productFrequencyRepository = $productFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
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
            SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_START_DATE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_TRIAL_START_DATE),
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
            SubscriptionProductAttributes::SUBSCRIPTION_START_DATE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_START_DATE),
            SubscriptionProductAttributes::SUBSCRIPTION_PURCHASE_TYPE =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_PURCHASE_TYPE),
            SubscriptionProductAttributes::SUBSCRIPTION_HIDE_QTY =>
                $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_HIDE_QTY),
        ];
        $productData = new DataObject();
        $productData->addData($data);

        return $productData;
    }

    /**
     * @param DataObject $product
     * @param array $productData
     * @param bool $full
     * @param bool $rowPrice
     * @return float|int|mixed|string
     * @throws NoSuchEntityException
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
        $lockProductPriceStatus =
            (bool) $product->getData(SubscriptionProductAttributes::SUBSCRIPTION_LOCK_PRODUCT_PRICE);
        $price = $this->priceCalculator->getUnitPrice(
            $product,
            $productData['billing_frequency'],
            isset($productData['price']) ? $productData['price'] : null,
            $full
        );
        if ($this->profileProduct) {
            $originProfileProductData = $this->profileProduct->getOrigData();
            $originUnitPrice = (float) $originProfileProductData['price'];
            $currentProfile = $this->getProfile();
            if (
                $this->config->getPricingStrategy() == PriceStrategy::GRANDFATHERED_PRICE
                && $currentProfile
                && $currentProfile->getOrigData('billing_frequency_id')
                    == $currentProfile->getData('billing_frequency_id')
            ) {
                $price = min($originUnitPrice, $price);
            }
        }
        if ($lockProductPriceStatus && $productQty) {
            $tierPrice = $this->productRepository
                ->getById($product->getData('child_product_id'))
                ->getTierPrice($productQty);
            if ($tierPrice) {
                $price = min($tierPrice, $price);
            }
        }
        if (!$rowPrice && $usePresetQty) {
            if (
                isset($productData['rebill_processing'])
                && $productData['rebill_processing']
            ) {
                $price = $productData['subscription_data']['non_unique']['current_preset_qty_price'];
            }
            $price = $productQty ? round($price, 4) : 0;
        }

        return $price;
    }

    /**
     * @return |null
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param $subscriptionProfile
     * @return $this
     */
    public function setProfile($subscriptionProfile)
    {
        $this->profile = $subscriptionProfile;
        return $this;
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

    /**
     * @param $productObject
     * @return $this
     */
    public function setOriginalProfileProduct($productObject)
    {
        $this->profileProduct = $productObject;
        return $this;
    }
}
