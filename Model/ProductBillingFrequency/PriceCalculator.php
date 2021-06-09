<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ProductBillingFrequency;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Backend\Product\Attribute\DiscountAmount;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\Collection;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

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
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * PriceCalculator constructor.
     * @param ProductRepository $productRepository
     * @param CollectionFactory $productBillingFrequencyCollectionFactory
     * @param Context $context
     * @param QuoteSessionInterface $session
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param RequestInterface $request
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        ProductRepository $productRepository,
        CollectionFactory $productBillingFrequencyCollectionFactory,
        Context $context,
        QuoteSessionInterface $session,
        FormatInterface $localeFormat,
        Session $customerSession,
        RequestInterface $request,
        CheckoutSession $checkoutSession
    ) {
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->collectionFactory = $productBillingFrequencyCollectionFactory;
        $this->context = $context;
        $this->session = $session;
        $this->localeFormat = $localeFormat;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
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
     * @param int|DataObject $product
     * @param int $billingFrequencyId
     * @param float|string $productPrice
     * @param bool $useTrial
     * @throws NoSuchEntityException when requested product doesn't exists in Db.
     * @return string
     */
    public function getUnitPrice(
        $product,
        $billingFrequencyId,
        $productPrice = null,
        $useTrial = false
    ) {
        $price = 0;
        if ($product && $billingFrequencyId) {
            $productPrice = $this->localeFormat->getNumber($productPrice);
            if (!($product instanceof DataObject)) {
                /** @var Product $product */
                $product = $this->productRepository->getById($product);
            }
            $trialOffered = $this->getTrialOfferedStatus($product);
            $lockProductPrice = $this->getProductLockPriceSatus($product);
            if ($trialOffered && $useTrial) {
                $trialPrice = $this->getTrialPrice($product);
                $price = $trialPrice ?: 0;
            } elseif ($lockProductPrice) {
                if ($tierPrice = $this->getProductTierPrice($product['child_product_id'])) {
                    $product['child_product_price'] = $tierPrice;
                }
                if ($product['child_product_id'] == $product['id']
                    && $product['child_product_price']
                ) {
                    $product['price'] = $product['child_product_price'];
                }
                if ($productPrice != null) {
                    $productPrice = $tierPrice;
                }
                $discountAmount = $this->getDiscountAmount($product, $productPrice);
                $origPrice = $this->convertToCurrency($product->getData('child_product_price'));
                $discountedPrice = ($origPrice - $discountAmount) >= 0 ? $origPrice - $discountAmount : 0;
                $price = isset($productPrice) ? $productPrice : $discountedPrice;
            } else {
                $billingFrequencyPrice = $this->getBillingFrequencyPrice(
                    $billingFrequencyId,
                    $product->getChildProductId()
                );
                $price = isset($productPrice) ? $productPrice : $billingFrequencyPrice;
            }
        }

        return (string)$price;
    }

    /**
     * @param $productId
     * @return mixed|null
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getProductTierPrice($productId)
    {
        $qty = false;
        $subscribeQty = $this->request->getParam('subscribe_qty')
            ? $this->request->getParam('subscribe_qty')
            : $this->request->getParam('item_qty');
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getItemsQty() && $this->request->getParam('subscribe_qty')){
            $qty = (int) $quote->getItemsQty() + (int) $subscribeQty;
        }
        $finalQty = $qty ? $qty : $subscribeQty;
        $productTierPrice = null;
        $product = $this->productRepository
            ->getById($productId);
        if ($product && $product->getId() && $product->getTierPrice()) {
            $customerGroupId = $this->customerSession->isLoggedIn()
                ? $this->customerSession->getCustomer()->getGroupId()
                : $this->customerSession->isLoggedIn();
            foreach ($product->getTierPrice() as $tierPrice) {
                if (($tierPrice['price_qty'] <= $finalQty || $tierPrice['price_qty'] == 1)
                    && ($tierPrice['cust_group'] == $customerGroupId
                        || $tierPrice['all_groups'])
                ) {
                    if (!$productTierPrice) {
                        //TODO: resolve against website
                        $productTierPrice = $tierPrice['price'];
                    } else {
                        $productTierPrice = min($productTierPrice, $tierPrice['price']);
                    }
                }
            }
        }
        return $productTierPrice;
    }

    /**
     * Get trial offered status.
     *
     * @param DataObject $product
     * @return bool
     */
    private function getTrialOfferedStatus(DataObject $product)
    {
        return $product->hasData(Attribute::SUBSCRIPTION_TRIAL_STATUS)
            ? (bool)$product->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS)
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
     * @param DataObject $product
     * @return float
     */
    private function getTrialPrice(DataObject $product)
    {
        $price = $product->hasData(Attribute::SUBSCRIPTION_TRIAL_PRICE)
            ? (float)$product->getData(Attribute::SUBSCRIPTION_TRIAL_PRICE)
            : 0;

        return $this->convertToCurrency($price);
    }

    /**
     * Get lock product price status.
     *
     * @param DataObject $product
     * @return bool
     */
    public function getProductLockPriceSatus(DataObject $product)
    {
        return $product->hasData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE)
            ? (bool)$product->getData(Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE)
            : false;
    }

    /**
     * Get offer flat discount status.
     *
     * @param DataObject $product
     * @return bool
     */
    private function getOfferFlatDiscount(DataObject $product)
    {
        return $product->hasData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT)
            ? (bool)$product->getData(Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT)
            : false;
    }

    /**
     * Get product discount amount considering discount type.
     *
     * @param DataObject $product
     * @param null|float $processPrice
     * @return float
     */
    private function getDiscountAmount(DataObject $product, $processPrice = null)
    {
        if ($processPrice === null) {
            $processPrice = $product->getData('price');
            if ($processPrice
                && $product->getData('id') == $product->getData('child_product_id')
                && $product->getData('child_product_price')
            ) {
                $processPrice = $product->getData('child_product_price');
            } elseif (!$processPrice) {
                $processPrice = $product->getData('child_product_price');
            }
        }
        $discountAmount = 0;
        if ($this->getOfferFlatDiscount($product)) {
            $discountType = $product->hasData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE)
                ? $product->getData(Attribute::SUBSCRIPTION_DISCOUNT_TYPE)
                : 0;
            if ($discountType) {
                $discountAmount = $product->hasData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT)
                    ? $product->getData(Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT)
                    : 0;
                if ($discountType == DiscountAmount::PERCENT_DISCOUNT && $discountAmount) {
                    $discountAmount = $processPrice * $discountAmount / 100;
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
     * @param $billingFrequencyId
     * @param $productId
     * @return DataObject
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
