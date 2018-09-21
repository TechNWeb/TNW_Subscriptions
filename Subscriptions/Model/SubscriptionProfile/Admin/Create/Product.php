<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
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

/**
 * Class Product
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
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCalculator $priceCalculator
     * @param ExtensionManager $extensionManager
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param ProductBillingFrequencyRepository $productBillingFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        ProductRepositoryInterface $productRepository,
        PriceCalculator $priceCalculator,
        ExtensionManager $extensionManager,
        ProductTypeManagerResolver $productTypeResolver,
        ProductBillingFrequencyRepository $productBillingFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->priceCalculator = $priceCalculator;
        $this->extensionManager = $extensionManager;
        $this->productTypeResolver = $productTypeResolver;
        $this->productBillingFrequencyRepository = $productBillingFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

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
            // add preset qty param to product request array
            $productData['use_preset_qty'] = (bool) $this->getProduct()
                ->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
            $product = $this->getProduct();
            $isTrial = $product->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS) ? true : false;
            $trialPeriod = $isTrial ? $product->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH) : null;
            $trialUnitId = $isTrial ? (int)$product->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT) : null;
            //Note: If product "is trial" then "start on" is start date of trial period,
            // otherwise "start on" is start date of subscription
            $startOn = $product->getData(Attribute::SUBSCRIPTION_START_DATE);
            if (isset($productData['start_on'])) {
                $startOn = $productData['start_on'];
            } elseif ($isTrial) {
                $startOn = $product->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE);
            }

            if ($productData['use_preset_qty']) {
                $this->searchCriteriaBuilder
                    ->addFilter(
                        ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
                        $this->getProduct()->getId()
                    )
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

            $data = [
                'qty' => $productData['qty'],
                'custom_price' => sprintf("%F", $this->getCustomPrice($product, $productData)),
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
                    ],
                    static::FULL_REQUEST_PARAM_NAME => true,
                ],
            ];
            if ($fullRequest) {
                $data = $this->addPricesToRequest($data, $productData);
            }
            //unset already unused fields
            unset(
                $productData['billing_frequency'],
                $productData['term'],
                $productData['period'],
                $productData['start_on']
            );
            $this->buyRequest = new DataObject(array_merge($data, $productData));
        }

        return $this->buyRequest;
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
        if ($origInitialFee > 0 && $initialFee > 0) {
            $quoteItemAttribute = $this->extensionManager->getEmptyQuoteItemAttribute()
                ->setBaseSubsInitialFee($origInitialFee)
                ->setSubsInitialFee($initialFee);
            $extensionAttributes = $item->getExtensionAttributes()
                ?: $this->extensionManager->getEmptyCartItemExtension();
            $extensionAttributes->setSubsInitialFees($quoteItemAttribute);
            $item->setExtensionAttributes($extensionAttributes);
        }
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
                    $result->add(new \DateInterval('P1M'));
                    $result = $result->format('Y-m-01');
                }
                break;

            case is_numeric($startOn) && $startOn == StartDateType::ON_15TH_OF_THE_MONTH:
                $result = date_create()->format('Y-m-15');

                if (strtotime($result) < strtotime($nowDate)) {
                    $result = new \DateTime();
                    $result->add(new \DateInterval('P1M'));
                    $result = $result->format('Y-m-15');
                }
                break;

            default:
                $result = date_create($startOn)->format('Y-m-d');

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
                        'current_preset_qty_price' => $this->getCurrentPresetQtyPrice($this->getProduct(), $productData),
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
}
