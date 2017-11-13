<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as ProductFrequencyRepository;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;

/**
 * Base class for buy request modifiers.
 */
abstract class Base implements TypeInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

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
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCalculator $priceCalculator
     * @param ProductFrequencyRepository $productFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        PriceCalculator $priceCalculator,
        ProductFrequencyRepository $productFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->priceCalculator = $priceCalculator;
        $this->productFrequencyRepository = $productFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Checks products if they have same billing frequency.
     *
     * @param $billingFrequency
     * @param $productIds
     * @throws LocalizedException
     */
    protected function checkFrequencyExistanse($billingFrequency, $productIds)
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
        $relations = $this->productFrequencyRepository->getList($searchCriteria)->getItems();

        if (count($relations) !== count($productIds)) {
            throw new LocalizedException(__('Not all products have the same frequency'));
        }
    }

    /**
     * Returns calculated product price.
     *
     * @param ProductInterface $product
     * @param array $productData
     * @param bool $full
     * @return float|string
     */
    protected function getCalculatedPrice(ProductInterface $product, array $productData, $full = false)
    {
        $usePresetQty = $product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
        //Calculate product Price
        $price = $this->priceCalculator->getUnitPrice(
            $product,
            $productData['billing_frequency'],
            isset($productData['price']) ? $productData['price'] : null,
            $full
        );

        if ($usePresetQty && $productData['qty'] !== 0) {
            $price = round($price / $productData['qty'], 4);
        }

        return $price;
    }
}
