<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\Format;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\QuoteSessionInterface;
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
     * @var Format
     */
    private $localeFormat;

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
        Format $localeFormat
    ) {
        $this->productRepository = $productRepository;
        $this->priceCalculator = $priceCalculator;
        $this->localeFormat = $localeFormat;
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
     * @return ProductInterface
     */
    public function getPreparedProduct()
    {
        $productData = $this->getData();
        $product = $this->productRepository->getById($productData['product_id']);

        $price = $this->priceCalculator->getUnitPrice(
            $product->getId(),
            $productData['billing_frequency_id'],
            $productData['price'],
            true
        );
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
            $product = $this->productRepository->getById($productData['product_id']);
            $isTrial = $product->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS) ? true : false;
            $trialPeriod = $isTrial ? $product->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH) : null;
            $trialUnitId = $isTrial ? (int)$product->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT) : null;

            //Note: If product "is trial" then "start on" is start date of trial period,
            // otherwise "start on" is start date of subscription
            $data = [
                'qty' => $productData['qty'],
                'custom_price' => $product->getPrice(),
                static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                    static::UNIQUE => [
                        'billing_frequency' => $productData['billing_frequency_id'],
                        'term' => $productData['term'],
                        'period' => $productData['period'],
                        'is_trial' => $isTrial,
                        'start_on' => $this->getStartOnDate($productData['start_on']),
                        'trial_period' => $trialPeriod,
                        'trial_unit_id' => $trialUnitId,
                    ],
                    static::NON_UNIQUE => [
                        'price' => $this->localeFormat->getNumber($productData['price'])
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
     * @return mixed
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
}
