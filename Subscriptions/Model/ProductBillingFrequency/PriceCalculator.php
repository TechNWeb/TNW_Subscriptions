<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Backend\Product\Attribute\DiscountAmount;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\Collection;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\CollectionFactory;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Calculate unit price for billing frequency.
 */
class PriceCalculator
{
    /**
     * Help retrieve product data from Db.
     *
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * Create collection for product billing frequency.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Context.
     *
     * @var Context
     */
    private $context;

    /**
     * Current session.
     *
     * @var QuoteSessionInterface
     */
    private $session;


    /**
     * PriceCalculator constructor.
     * @param ProductRepository $productRepository
     * @param CollectionFactory $productBillingFrequencyCollectionFactory
     * @param Context $context
     */
    public function __construct(
        ProductRepository $productRepository,
        CollectionFactory $productBillingFrequencyCollectionFactory,
        Context $context,
        QuoteSessionInterface $session
    ) {
        $this->productRepository = $productRepository;
        $this->collectionFactory = $productBillingFrequencyCollectionFactory;
        $this->context = $context;
        $this->session = $session;
    }

    /**
     * Get calculated product price for billing frequency.
     *
     * Return calculated product price based on conditions:
     * If product "Is trial offered" is "Yes" and $useTrial is true and "Trial price" > 0 then:
     *     price = "Trial price"(product).
     *     (if "initial fee" should be calculated on current step")
     * If product "Is trial offered" is "Yes" and $useTrial is true and "Trial price" = 0 then:
     *     price = 0.
     *
     * If product "Is trial offered" is "No" then:
     *     If "Lock product price"(product) = "No" then:
     *         If isset $productPrice then price = $productPrice
     *         else price = "Price"(billing frequency).
     *     If "Lock product price"(product) = "Yes" then:
     *         price = "Price"(product) - "Discount amount"(product)
     *         (if "Offer flat discount" = On).
     *
     * "Discount amount" calculated based on conditions:
     *    If "Discount amount type" = "Flat fee" then:
     *        "Discount amount" = "Discount amount"(product).
     *    If "Discount amount type" = "Percent" then:
     *        "Discount amount" = "Price"(product) * "Discount amount"(product).
     *
     * @param int $productId
     * @param int $billingFrequencyId
     * @param float|string $productPrice
     * @param bool $useTrial
     * @throws NoSuchEntityException when requested product doesn't exists in Db.
     * @return string
     */
    public function getUnitPrice(
        $productId,
        $billingFrequencyId,
        $productPrice = null,
        $useTrial = false
    ) {
        $price = 0;
        if ($productId && $billingFrequencyId) {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId);
            $trialOffered = $this->getTrialOfferedStatus($product);
            $lockProductPrice = $this->getProductLockPriceSatus($product);
            if ($trialOffered && $useTrial) {
                $trialPrice = $this->getTrialPrice($product);
                $price = $trialPrice ?: 0;
            } else {
                if ($lockProductPrice) {
                    $discountAmount = $this->getDiscountAmount($product);
                    $origPrice = $this->convertToCurrency($product->getOrigData('price'));
                    $price= isset($productPrice) ? $productPrice : $origPrice - $discountAmount;
                } else {
                    $billingFrequencyPrice = $this->getBillingFrequencyPrice($billingFrequencyId, $productId);
                    $price = isset($productPrice) ? $productPrice : $billingFrequencyPrice;
                }
            }
        }

        return (string)$price;
    }

    /**
     * Get trial offered status.
     *
     * @param Product $product
     * @return bool
     */
    private function getTrialOfferedStatus(Product $product)
    {
        return $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_STATUS)
            ? (bool)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_STATUS)->getValue()
            : false;
    }

    /**
     * Get billing frequency initial fee.
     *
     * @param int $billingFrequencyId
     * @param int $productId
     * @param bool $convert
     * @return float
     */
    public function getInitialFee($billingFrequencyId, $productId, $convert = true)
    {
        $productBillingFrequency = $this->getProductBillingFrequency($billingFrequencyId, $productId);
        $initialFee = (float)$productBillingFrequency->getInitialFee() ?: 0;

        return $convert ? $this->convertToCurrency($initialFee) : $initialFee;
    }

    /**
     * Get trial price value for product.
     *
     * @param Product $product
     * @return float
     */
    private function getTrialPrice(Product $product)
    {
        $price = $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_PRICE)
            ? (float)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_PRICE)->getValue()
            : 0;

        return $this->convertToCurrency($price);
    }

    /**
     * Get lock product price status.
     *
     * @param Product $product
     * @return bool
     */
    private function getProductLockPriceSatus(Product $product)
    {
        return $product->getCustomAttribute(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE)
            ? (bool)$product->getCustomAttribute(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE)->getValue()
            : false;
    }

    /**
     * Get offer flat discount status.
     *
     * @param Product $product
     * @return bool
     */
    private function getOfferFlatDiscount(Product $product)
    {
        return $product->getCustomAttribute(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT)
            ? (bool)$product->getCustomAttribute(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT)->getValue()
            : false;
    }

    /**
     * Get product discount amount considering discount type.
     *
     * @param Product $product
     * @return float
     */
    private function getDiscountAmount(Product $product)
    {
        $discountAmount = 0;
        if ($this->getOfferFlatDiscount($product)) {
            $discountType = $product->getCustomAttribute(Attribute::SUBSCRIPTION_DISCOUNT_TYPE)
                ? $product->getCustomAttribute(Attribute::SUBSCRIPTION_DISCOUNT_TYPE)->getValue()
                : 0;
            if ($discountType) {
                $discountAmount = $product->getCustomAttribute(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT)
                    ? $product->getCustomAttribute(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT)->getValue()
                    : 0;
                if ($discountType == DiscountAmount::PERCENT_DISCOUNT && $discountAmount) {
                    $discountAmount = $product->getPrice() * $discountAmount / 100;
                }
            }
        }

        return $this->convertToCurrency($discountAmount);
    }

    /**
     * Get billing frequency price.
     *
     * @param $billingFrequencyId
     * @param $productId
     * @return float
     */
    private function getBillingFrequencyPrice($billingFrequencyId, $productId)
    {
        $productBillingFrequency = $this->getProductBillingFrequency($billingFrequencyId, $productId);
        $price = (float)$productBillingFrequency->getPrice() ?: 0;

        return $this->convertToCurrency($price);
    }

    /**
     * Get product billing frequency considering billing frequency and product.
     *
     * @param $billingFrequencyId
     * @param $productId
     * @return ProductBillingFrequencyInterface
     */
    public function getProductBillingFrequency($billingFrequencyId, $productId)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(ProductBillingFrequencyInterface::INITIAL_FEE);
        $collection->addFieldToSelect(ProductBillingFrequencyInterface::PRICE);
        $collection->addFieldToFilter(ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID, $billingFrequencyId);
        $collection->addFieldToFilter(ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID, $productId);

        return $collection->getFirstItem();
    }

    /**
     * Converts price to currency.
     *
     * @param string|float value
     * @return float
     */
    private function convertToCurrency($value)
    {
        return $this->context->getPriceCurrency()->convert(
            $value,
            $this->session->getStoreId(),
            $this->session->getCurrencyId()
        );
    }
}
