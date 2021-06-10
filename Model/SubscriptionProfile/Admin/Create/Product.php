<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\ProductBillingFrequencyRepository;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\PriceStrategy;
use TNW\Subscriptions\Service\Serializer;

/**
 * Class Product - model for subcription profile product
 */
class Product extends Create
{
    /**
     * Repository for retrieving products.
     *
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Calculator for retrieving product price.
     *
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * Buy request for adding to product to quote.
     *
     * @var DataObject
     */
    private $buyRequest;

    /**
     * @var []
     */
    private $data;

    /**
     * Quote item extension attribute manager.
     *
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * @var ProductTypeManagerResolver
     */
    private $productTypeResolver;

    /**
     * Current used product.
     *
     * @var MagentoProduct
     */
    private $product;

    /**
     * Current used child product.
     * @var MagentoProduct
     */
    private $childProduct;

    /**
     * Attributes Inheritance config
     * @var array
     */
    private $inheritanceConfig;

    /**
     * Current used product DataObject.
     *
     * @var DataObject
     */
    private $productDataObject;

    /**
     * @var ProductBillingFrequencyRepository
     */
    private $productBillingFrequencyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Product constructor.
     * @param Config $config
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCalculator $priceCalculator
     * @param ExtensionManager $extensionManager
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param ProductBillingFrequencyRepository $productBillingFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Serializer $serializer
     */
    public function __construct(
        Config $config,
        Context $context,
        QuoteSessionInterface $session,
        ProductRepositoryInterface $productRepository,
        PriceCalculator $priceCalculator,
        ExtensionManager $extensionManager,
        ProductTypeManagerResolver $productTypeResolver,
        ProductBillingFrequencyRepository $productBillingFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Serializer $serializer
    ) {
        $this->config = $config;
        $this->productRepository = $productRepository;
        $this->priceCalculator = $priceCalculator;
        $this->extensionManager = $extensionManager;
        $this->productTypeResolver = $productTypeResolver;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->serializer = $serializer;

        parent::__construct($context, $session);
    }

    /**
     * Resets buy request and data array.
     *
     * @return void
     */
    public function reset()
    {
        $this->buyRequest = null;
        $this->data = [];
        $this->productDataObject = null;
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->buyRequest = null;
        $this->product = null;
        $this->productDataObject = null;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns product from data array.
     *
     * @return MagentoProduct
     */
    public function getProduct()
    {
        if (null === $this->product) {
            $productData = $this->getData();
            $this->product = $this->loadProduct($productData['product_id']);
        }

        return $this->product;
    }

    /**
     * Sets product.
     *
     * @param MagentoProduct $product
     * @return $this
     */
    public function setProduct(MagentoProduct $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Returns product DataObject from MagentoProduct.
     *
     * @return DataObject
     */
    public function getProductDataObject()
    {
        if (null === $this->productDataObject) {
            $requestData = $this->getData();
            $product = $this->getProduct();
            $this->productDataObject = $this->productTypeResolver->resolve($product->getTypeId())
                ->getProductDataObject($product, $requestData);
        }

        return $this->productDataObject;
    }

    /**
     * Returns prepared product buy request.
     *
     * @param bool $fullRequest
     * @return DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPreparedBuyRequest($fullRequest = false)
    {
        if (!$this->buyRequest) {
            $productData = $this->getData();
            $product = $this->getProduct();
            if ($this->getProduct()->getTypeId() === Configurable::TYPE_CODE
                && isset($productData['super_attribute'])
            ) {
                $childProduct = $this->getProduct()->getTypeInstance()
                    ->getProductByAttributes($productData['super_attribute'], $product);
                if ($childProduct instanceof MagentoProduct) {
                    $this->setChildProduct($childProduct);
                }
            }
            // add preset qty param to product request array
            $productData['use_preset_qty'] = $this->getChildProduct()
                ? (bool) $this->getChildProduct()->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY)
                : (bool) $this->getProduct()->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
            $productData['hide_qty'] =
                (bool) $this->getSubsAttribute(Attribute::SUBSCRIPTION_HIDE_QTY);
            $startOn = $this->getSubsAttribute(Attribute::SUBSCRIPTION_START_DATE);
            $isTrial = false;
            if (isset($productData['use_trial']) && filter_var($productData['use_trial'], FILTER_VALIDATE_BOOLEAN)
                && $this->getSubsAttribute(Attribute::SUBSCRIPTION_TRIAL_STATUS)
                && $this->getSubsAttribute(Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP)
                && (empty($productData['modify_profile']) || !$productData['modify_profile'])
            ) {
                $isTrial = true;
            } else {
                $productData['modify_profile'] = true;
            }
            $trialPeriod = $trialUnitId = null;
            if ($isTrial) {
                $childTrial = $this->getInheritanceConfig(Attribute::SUBSCRIPTION_TRIAL_STATUS);
                $trialPeriod = $childTrial
                    ? $this->getChildProduct()->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH)
                    : $this->getProduct()->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH);
                $trialUnitId = $childTrial
                    ? (int) $this->getChildProduct()->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT)
                    : (int) $this->getProduct()->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT);
                //Note: If product "is trial" then "start on" is start date of trial period,
                // otherwise "start on" is start date of subscription
                $startOn = $childTrial
                    ? $this->getChildProduct()->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE)
                    : $this->getProduct()->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE);
            }
            if (isset($productData['start_on'])) {
                $startOn = $productData['start_on'];
            }
            if ($productData['use_preset_qty']) {
                if ($this->getChildProduct()) {
                    $this->searchCriteriaBuilder
                        ->addFilter(
                            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                            $this->getChildProduct()->getId()
                        );
                } else {
                    $this->searchCriteriaBuilder
                        ->addFilter(
                            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                            $this->getProduct()->getId()
                        );
                }
                $this->searchCriteriaBuilder
                    ->addFilter(
                        ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID,
                        $productData['billing_frequency']
                    );

                $searchOptions = $this->productBillingFrequencyRepository
                    ->getList($this->searchCriteriaBuilder->create())
                    ->getItems();

                if (!empty($searchOptions)) {
                    $productData['qty'] = reset($searchOptions)->getPresetQty();
                }
            }

            $currentProductCustomPrice = $this->getCustomPrice($product, $productData);
            $customProductPrice = $this->getRebillProcessing()
                ? (float) $this->getPresetCustomPrice()
                : (float) $currentProductCustomPrice;
            if ($this->config->getPricingStrategy() == PriceStrategy::GRANDFATHERED_PRICE
                && isset($this->data['custom_price'])
            ) {
                if (isset($this->data['use_preset_qty'])
                    && $this->data['use_preset_qty']
                    && isset($this->data['subscription_data']['non_unique']['current_preset_qty_price'])
                ) {
                    $customProductPrice = (float) $this->data
                    ['subscription_data']
                    ['non_unique']
                    ['current_preset_qty_price'];
                } else {
                    $customProductPrice = (float) $this->data['custom_price'];
                }
            }
            if ($this->config->getPricingStrategy() != PriceStrategy::GRANDFATHERED_PRICE
                && isset($productData['admin_modification'])
                && $productData['admin_modification']
            ) {
                if ($productData['use_preset_qty']
                    && isset($productData['subscription_data']['non_unique']['current_preset_qty_price'])
                ) {
                    $customProductPrice = $productData['subscription_data']['non_unique']['current_preset_qty_price'];
                }
                $currentProductCustomPrice = $customProductPrice;
                $productData['price'] = $customProductPrice;
            } elseif (isset($productData['rebill_processing'])
                && $productData['rebill_processing']
                && $productData['use_preset_qty']
            ) {
                $customProductPrice = $currentProductCustomPrice = $productData
                ['subscription_data']
                ['non_unique']
                ['current_preset_qty_price'];
            }
            if (isset($productData['modify_profile'])
                && isset($productData['admin_modification'])
                && $productData['modify_profile']
                && $productData['admin_modification']
            ) {
                $currentProductCustomPrice = $customProductPrice;
            }
            $customProductPrice = min($currentProductCustomPrice, $customProductPrice);

            $data = [
                'custom_price' => sprintf('%F', $customProductPrice),
                static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                    static::UNIQUE => [
                        'billing_frequency' => $productData['billing_frequency'],
                        'term' => !empty($productData['term']) ? 1 : 0,
                        'period' => !empty($productData['term']) ? 0 : $productData['period'],
                        'is_trial' => $isTrial,
                        'start_on' => $this->getStartOnDate($startOn),
                        'trial_period' => $trialPeriod,
                        'trial_unit_id' => $trialUnitId,
                        'use_preset_qty' => $productData['use_preset_qty'],
                        'hide_qty' => $productData['hide_qty'],
                    ],
                    static::FULL_REQUEST_PARAM_NAME => true,
                ],
            ];

            if ($fullRequest) {
                $data = $this->addPricesToRequest($data, $productData);
            }

            $this->buyRequest = new DataObject(array_merge($productData, $data));
        }

        return $this->buyRequest;
    }

    /**
     * @return bool
     */
    public function getRebillProcessing()
    {
        return isset($this->data['rebill_processing']) ? (bool) $this->data['rebill_processing'] : false;
    }

    /**
     * @return mixed
     */
    public function getPresetCustomPrice()
    {
        return (float) $this->data['custom_price'];
    }

    /**
     * Sets initial fee to quote item.
     *
     * @param Item $item
     * @return void
     */
    public function setInitialFeeToItem(Item $item)
    {
        $requestData = $this->getData();
        $origInitialFee = $this->getInitialFee($requestData, false);
        $initialFee = $this->getInitialFee($requestData, true);
        $quoteItemAttribute = $this->extensionManager->getEmptyQuoteItemAttribute()
            ->setBaseSubsInitialFee($origInitialFee)
            ->setSubsInitialFee($initialFee);
        $extensionAttributes = $item->getExtensionAttributes()
            ?: $this->extensionManager->getEmptyCartItemExtension();
        $extensionAttributes->setSubsInitialFees($quoteItemAttribute);
        $item->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Return product initial fee.
     *
     * @param array $requestData
     * @param bool $convert
     * @return float
     */
    private function getInitialFee(array $requestData, $convert)
    {
        $productDataObject = $this->getProductDataObject();

        return $this->priceCalculator->getInitialFee(
            $requestData['billing_frequency'],
            $productDataObject->getChildProductId(),
            $convert
        );
    }

    /**
     * Calculates start date for subscription.
     *
     * @param string|int $startOn
     * @return string
     */
    private function getStartOnDate($startOn)
    {
        $nowDate = date_create()->format('Y-m-d');
        switch (true) {
            case is_numeric($startOn) && $startOn == StartDateType::LAST_DAY_OF_THE_CURRENT_MONTH:
                $result = date_create()->format('Y-m-t');
                break;

            case is_numeric($startOn) && $startOn == StartDateType::MOMENT_OF_PURCHASE:
                $result = date_create()->format('Y-m-d');
                break;

            case is_numeric($startOn) && $startOn == StartDateType::FIRST_DAY_OF_THE_MONTH:
                $result = date_create()->format('Y-m-01');

                if (strtotime($result) < strtotime($nowDate)) {
                    $result = new \DateTime();
                    $nextPeriodMonth = $result->format('m') + 1;
                    $result = $result->format('Y-') . $nextPeriodMonth . '-' . '01';
                }
                break;

            case is_numeric($startOn) && $startOn == StartDateType::ON_15TH_OF_THE_MONTH:
                $result = date_create()->format('Y-m-15');

                if (strtotime($result) < strtotime($nowDate)) {
                    $result = new \DateTime();
                    $nextPeriodMonth = $result->format('m') + 1;
                    $result = $result->format('Y-') . $nextPeriodMonth . '-' . '15';
                }
                break;

            default:
                $result = date_create($startOn);
                if (!$result) {
                    $result = date_create($nowDate);
                }
                $result = $result->format('Y-m-d');

                if (strtotime($result) < strtotime($nowDate)) {
                    $result = $nowDate;
                }
                break;
        }

        return $result;
    }

    /**
     * Returns product.
     *
     * @param string|int $productId
     * @return MagentoProduct
     */
    private function loadProduct($productId)
    {
        return $this->productRepository->getById($productId);
    }

    /**
     * Adds prices to buy request array.
     *
     * @param array $data
     * @param array $productData
     * @return array
     */
    private function addPricesToRequest(array $data, array $productData)
    {
        $initialFee = $this->getInitialFee($productData, true);
        $data = array_merge_recursive(
            $data,
            [
                static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                    static::NON_UNIQUE => [
                        'current_price' => $this->getCustomPrice($this->getProduct(), $productData),
                        'initial_fee' =>  (float)$initialFee,
                        'price' => $this->getPrice($this->getProduct(), $productData),
                        'current_preset_qty_price' => $this->getCurrentPresetQtyPrice(
                            $this->getProduct(),
                            $productData
                        ),
                        'preset_qty_price' => $this->getPresetQtyPrice($this->getProduct(), $productData),
                    ]
                ],
            ]
        );

        return $data;
    }

    /**
     * Returns product subscription custom price.
     *
     * @param MagentoProduct $product
     * @param array $productData
     * @return string
     */
    private function getCustomPrice(MagentoProduct $product, array $productData)
    {
        return $this->productTypeResolver->resolve($product->getTypeId())
            ->getSubscriptionCustomPrice($product, $productData);
    }

    /**
     * Returns product subscription price.
     *
     * @param MagentoProduct $product
     * @param array $productData
     * @return string
     */
    private function getPrice(MagentoProduct $product, array $productData)
    {
        return $this->productTypeResolver->resolve($product->getTypeId())
            ->getSubscriptionPrice($product, $productData);
    }

    /**
     * Returns product subscription preset qty price.
     * Used for products with preset qty and returns the price for the whole quantity.
     *
     * @param MagentoProduct $product
     * @param array $productData
     * @return string
     */
    private function getPresetQtyPrice(MagentoProduct $product, array $productData)
    {
        return $this->productTypeResolver->resolve($product->getTypeId())
            ->getSubscriptionPresetQtyPrice($product, $productData);
    }

    /**
     * Returns product subscription trial preset qty price.
     * Used for products with preset qty and returns the price for the whole quantity.
     *
     * @param MagentoProduct $product
     * @param array $productData
     * @return string
     */
    private function getCurrentPresetQtyPrice(MagentoProduct $product, array $productData)
    {
        return $this->productTypeResolver->resolve($product->getTypeId())
            ->getSubscriptionCurrentPresetQtyPrice($product, $productData);
    }

    /**
     * Get attribute value according to inheritance config
     * @param $code
     * @return mixed|null
     */
    private function getSubsAttribute($code)
    {
        if ($this->getChildProduct()
            && $this->getInheritanceConfig()
            && isset($this->getInheritanceConfig()[$code])
            && (bool)$this->getInheritanceConfig()[$code]
        ) {
            return $this->getChildProduct()->getData($code);
        }
        return $this->getProduct()->getData($code);
    }

    /**
     * @return MagentoProduct
     */
    private function getChildProduct()
    {
        return $this->childProduct;
    }

    /**
     * @param MagentoProduct $childProduct
     */
    private function setChildProduct(MagentoProduct $childProduct)
    {
        $this->childProduct = $childProduct;
    }

    /**
     * @param null $code
     * @return array|bool
     */
    private function getInheritanceConfig($code = null)
    {
        if (null === $this->inheritanceConfig) {
            $inheritance = $this->getProduct()->getData(Attribute::SUBSCRIPTION_INHERITANCE);
            if ($this->getProduct()->getTypeId() === Configurable::TYPE_CODE && !empty($inheritance)) {
                $this->inheritanceConfig = $this->serializer->unserialize($inheritance);
            }
        }
        if (null !== $code && isset($this->inheritanceConfig[$code])) {
            return (bool)$this->inheritanceConfig[$code];
        }
        return $this->inheritanceConfig ?? false;
    }
}
