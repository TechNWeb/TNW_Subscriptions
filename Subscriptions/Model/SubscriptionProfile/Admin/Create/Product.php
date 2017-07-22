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
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier\Trial;
use TNW\Subscriptions\Model\SubscriptionProfile\Admin\AbstractCreate;

class Product extends AbstractCreate
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
     * Product constructor.
     * @param Context $context
     * @param Quote $session
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCalculator $priceCalculator
     */
    public function __construct(
        Context $context,
        Quote $session,
        ProductRepositoryInterface $productRepository,
        PriceCalculator $priceCalculator
    ) {
        $this->productRepository = $productRepository;
        $this->priceCalculator = $priceCalculator;
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
            $productData['product_billing_frequency'],
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

            //Note: If product "is trial" then "start on" is start date of trial period,
            // otherwise "start on" is start date of subscription
            $data = [
                'qty' => $productData['qty'],
                'custom_price' => $product->getPrice(),
                static::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME => [
                    'unique' => [
                        'billing_frequency' => $productData['product_billing_frequency'],
                        'term' => $productData['term'],
                        'period' => $productData['period'],
                        'is_trial' => $product->getData(Trial::CODE_TRIAL) ? true : false,
                        'start_on' => $this->getStartOnDate($productData['start_on']),
                        'trial_period' => $product->getData(Trial::CODE_TRIAL_LENGTH),
                        'trial_unit_id' => (int)$product->getData(Trial::CODE_TRIAL_LENGTH_UNIT),
                    ],
                    'non_unique' => [
                        'price' => $this->priceCalculator->getUnitPrice(
                            $product->getId(),
                            $productData['product_billing_frequency'],
                            $productData['price']
                        )
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