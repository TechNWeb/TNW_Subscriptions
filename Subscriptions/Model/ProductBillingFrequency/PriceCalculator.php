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
     * PriceCalculator constructor.
     *
     * @param ProductRepository $productRepository
     * @param CollectionFactory $productBillingFrequencyCollectionFactory
     */
    public function __construct(
        ProductRepository $productRepository,
        CollectionFactory $productBillingFrequencyCollectionFactory
    ) {
        $this->productRepository = $productRepository;
        $this->collectionFactory = $productBillingFrequencyCollectionFactory;
    }

    /**
     * Get calculated product price for billing frequency.
     *
     * Return calculated product price based on conditions:
     * If product "Is trial offered" is "Yes" and $useTrial is true and "Trial price" > 0 then:
     *     price = "Trial price"(product) + "Initial fee"(billing frequency).
     *     (if "initial fee" should be calculated on current step")
     * If product "Is trial offered" is "Yes" and $useTrial is true and "Trial price" = 0 then:
     *     price = 0.
     *
     * If product "Is trial offered" is "No" then:
     *     If "Lock product price"(product) = "No" then:
     *         If isset $productPrice then price = $productPrice
     *         else price = "Price"(billing frequency) + "Initial fee"(billing frequency).
     *     If "Lock product price"(product) = "Yes" then:
     *         price = "Price"(product) + "Initial fee"(billing frequency) - "Discount amount"(product)
     *         (if "Offer flat discount" = On).
     *(if "initial fee" should be calculated on current step")
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
     * @param bool $useInitialFee
     * @throws NoSuchEntityException when requested product doesn't exists in Db.
     * @return string
     */
    public function getUnitPrice(
        $productId,
        $billingFrequencyId,
        $productPrice = null,
        $useTrial = false,
        $useInitialFee = true
    ) {
        $price = 0;
        if ($productId && $billingFrequencyId) {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId);
            $trialOffered = $this->getTrialOfferedStatus($product);
            $initialFee = $this->getInitialFee($billingFrequencyId, $productId, $useInitialFee);
            $lockProductPrice = $this->getProductLockPriceSatus($product);
            if ($trialOffered && $useTrial) {
                $trialPrice = $this->getTrialPrice($product);
                $price = $trialPrice ? $trialPrice + $initialFee : 0;
            } else {
                if ($lockProductPrice) {
                    $discountAmount = $this->getDiscountAmount($product);
                    $lockPrice = isset($productPrice) ? $productPrice : $product->getOrigData('price') - $discountAmount;
                    $price = $lockPrice + $initialFee;
                } else {
                    $billingFrequencyPrice = $this->getBillingFrequencyPrice($billingFrequencyId, $productId);
                    $billingFrequencyPrice = isset($productPrice) ? $productPrice : $billingFrequencyPrice;
                    $price = $billingFrequencyPrice + $initialFee;
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
     * @param bool $useInitialFee
     * @return float
     */
    public function getInitialFee($billingFrequencyId, $productId, $useInitialFee)
    {
        $initialFee = 0;
        if ($useInitialFee) {
            $productBillingFrequency = $this->getProductBillingFrequency($billingFrequencyId, $productId);
            $initialFee = (float)$productBillingFrequency->getInitialFee() ?: 0;
        }

        return $initialFee;
    }

    /**
     * Get trial price value for product.
     *
     * @param Product $product
     * @return float
     */
    private function getTrialPrice(Product $product)
    {
        return $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_PRICE)
            ? (float)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_PRICE)->getValue()
            : 0;
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

        return $discountAmount;
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

        return (float)$productBillingFrequency->getPrice() ?: 0;
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
}
