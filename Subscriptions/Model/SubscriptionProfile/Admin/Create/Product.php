<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\Format;
use Magento\Quote\Model\Quote\Item;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;

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
     * @var Format
     */
    private $localeFormat;

    /**
     * Quote item extension attribute manager.
     *
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * Product constructor.
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCalculator $priceCalculator
     * @param Format $localeFormat
     */
    public function __construct(
        Context $context,
        QuoteSessionInterface $session,
        ProductRepositoryInterface $productRepository,
        PriceCalculator $priceCalculator,
        Format $localeFormat,
        ExtensionManager $extensionManager
    ) {
        $this->productRepository = $productRepository;
        $this->priceCalculator = $priceCalculator;
        $this->localeFormat = $localeFormat;
        $this->extensionManager = $extensionManager;
        parent::__construct($context, $session);
    }

    /**
     * Returns buy request.
     *
     * @return DataObject
     */
    public function getBuyRequest()
    {
        return $this->buyRequest;
    }

    public function reset()
    {
        $this->buyRequest = null;
        $this->data = [];
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Prepares product to adding product in to quote.
     *
     * @return MagentoProduct
     */
    public function getPreparedProduct()
    {
        $productData = $this->getData();
        /** @var MagentoProduct $product */
        $product = $this->getProduct($productData['product_id']);
        $price = $this->getCalculatedPrice($productData, true);
        $product->setPrice($price);

        return $product;
    }

    /**
     * Returns prepared product buy request.
     *
     * @return DataObject
     */
    public function getPreparedBuyRequest()
    {
        if (!$this->buyRequest) {
            $productData = $this->getData();
            /** @var MagentoProduct $product */
            $product = $this->getProduct($productData['product_id']);
            $isTrial = $product->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS) ? true : false;
            $trialPeriod = $isTrial ? $product->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH) : null;
            $trialUnitId = $isTrial ? (int)$product->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT) : null;
            $startOn = isset($productData['start_on']) ?
                $productData['start_on'] : $product->getData(Attribute::SUBSCRIPTION_START_DATE);

            //Note: If product "is trial" then "start on" is start date of trial period,
            // otherwise "start on" is start date of subscription
            $data = [
                'qty' => $productData['qty'],
                'custom_price' => sprintf("%F", $product->getPrice()),
                static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                    static::UNIQUE => [
                        'billing_frequency' => $productData['billing_frequency'],
                        'term' => !empty($productData['term']) ? 1 : 0,
                        'period' => !empty($productData['term']) ? 0 : $productData['period'],
                        'is_trial' => $isTrial,
                        'start_on' => $this->getStartOnDate($startOn),
                        'trial_period' => $trialPeriod,
                        'trial_unit_id' => $trialUnitId,
                    ],
                    static::NON_UNIQUE => [
                        'price' => $this->getCalculatedPrice($productData)
                    ],
                ],
            ];
            $this->buyRequest = new DataObject($data);
        }

        return $this->buyRequest;
    }

    /**
     * Calculates start date for subscription.
     *
     * @param $startOn
     * @return string
     */
    private function getStartOnDate($startOn)
    {
        switch ($startOn) {
            case StartDateType::LAST_DAY_OF_THE_CURRENT_MONTH:
                $result = new \DateTime();
                $result = $result->format('Y-m-t');
                break;
            case StartDateType::MOMENT_OF_PURCHASE:
                $result = new \DateTime();
                $result = $result->format('Y-m-d');
                break;
            default:
                $result = new \DateTime($startOn);
                $result = $result->format('Y-m-d');
                break;
        }

        return $result;
    }

    /**
     * @param array $productData
     * @param bool $full
     * @return string
     */
    private function getCalculatedPrice(array $productData, $full = false)
    {
        $usePresetQty = $this->getProduct($productData['product_id'])
            ->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);

        //Calculate product Price
        $price = $this->priceCalculator->getUnitPrice(
            $productData['product_id'],
            $productData['billing_frequency'],
            $this->localeFormat->getNumber(isset($productData['price']) ? $productData['price'] : null),
            $full
        );

        if ($usePresetQty){
            $price = round($price / $productData['qty'], 4);
        }

        return $price;
    }

    /**
     * Sets initial fee to quote item.
     *
     * @param Item $item
     */
    public function setInitialFeeToItem(Item $item)
    {
        $requestData = $this->getData();
        $origInitialFee = $this->priceCalculator->getInitialFee(
            $requestData['billing_frequency'],
            $requestData['product_id'],
            false
        );
        $initialFee = $this->priceCalculator->getInitialFee(
            $requestData['billing_frequency'],
            $requestData['product_id'],
            true
        );
        if ($origInitialFee > 0 && $initialFee > 0){
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
     * Returns product.
     *
     * @param $productId
     * @return MagentoProduct
     */
    private function getProduct($productId)
    {
        /** @var MagentoProduct $product */
        $product = $this->productRepository->getById($productId);

        return $product;
    }
}
