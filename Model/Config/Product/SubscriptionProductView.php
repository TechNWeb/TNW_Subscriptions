<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Model\Config\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use Magento\Customer\Model\Session;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class subscription product view config is available subscription.
 */
class SubscriptionProductView
{
    /**
     * Subscription module config
     *
     * @var Config
     */
    private $config;

    /**
     * Modal form for adding single product to subscription
     *
     * @var FrequencyOptionRepository
     */
    private $frequencyOptionRepository;

    /**
     * Product collection.
     *
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductTypeManagerResolver
     */
    private $productTypeResolver;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Config $config
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param RequestInterface $request
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        FrequencyOptionRepository $frequencyOptionRepository,
        RequestInterface $request,
        ProductCollectionFactory $productCollectionFactory,
        ProductTypeManagerResolver $productTypeResolver,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->frequencyOptionRepository = $frequencyOptionRepository;
        $this->request = $request;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productTypeResolver = $productTypeResolver;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Get "Enable Subscriptions" config value for current website
     *
     * @param ProductInterface $product
     * @return bool
     * @throws LocalizedException
     */
    public function isSubscribeAvailable($product)
    {
        if ($product->getTypeId() === Grouped::TYPE_CODE) {
            $result = false;
            $childrenData = $this->isSubscribeAvailableByIds(
                $product->getTypeInstance()->getChildrenIds($product->getId())
            );
            foreach ($childrenData as $id => $isSubscription) {
                if ($isSubscription && $this->getProductBillingFrequenciesById($id)) {
                    $result = true;
                }
            }
            return $this->config->isSubscriptionsActiveCurrent() && $result;
        }
        return
            $this->config->isSubscriptionsActiveCurrent()
            && !empty($this->getProductBillingFrequencies($product))
            && $this->getCustomerGroupLimitation($product);
    }

    /**
     * Get "Enable Subscriptions" config value for current website by product id.
     *
     * @param int $productId
     * @return bool
     * @throws LocalizedException
     */
    public function isSubscribeAvailableById($productId)
    {

        return
            $this->config->isSubscriptionsActiveCurrent()
            && !empty($this->getProductBillingFrequenciesById($productId))
            && $this->getCustomerGroupLimitation(null, $productId);
    }

    /**
     * @param array $productIds
     * @return array
     */
    public function isSubscribeAvailableByIds(array $productIds)
    {
        $result = [];
        foreach ($this->getProductSubscriptionPurchaseTypeByIds($productIds) as $key => $element) {
            $result[$key] = false;
            if (($element[Attribute::SUBSCRIPTION_PURCHASE_TYPE] == PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE
                || $element[Attribute::SUBSCRIPTION_PURCHASE_TYPE] == PurchaseType::RECURRING_PURCHASE_TYPE)
                && $element['is_salable']) {
                $result[$key] = true;
            }
        }
        return $result;
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isOnlySubscribePurchase(ProductInterface $product)
    {
        return ($this->getProductSubscriptionPurchaseType($product) == PurchaseType::RECURRING_PURCHASE_TYPE)
            && $product->getIsSalable();
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only for products ids.
     * Retrieve array like product_id => boolean
     *
     * @param array $productPreset
     * @return array
     */
    public function isOnlySubscribePurchaseByIds(array $productPreset)
    {
        $result = [];
        $productIds = array_keys($productPreset);
        foreach ($this->getProductSubscriptionPurchaseTypeByIds($productIds) as $key => $element) {
            $result[$key] = false;
            if ($element[Attribute::SUBSCRIPTION_PURCHASE_TYPE] == PurchaseType::RECURRING_PURCHASE_TYPE
                && $element['is_salable']) {
                $result[$key] = true;
            }
        }
        return $result;
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" and "One time purchase".
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function isOneTimeAndSubscribePurchase(ProductInterface $product)
    {
        return ($this->getProductSubscriptionPurchaseType($product)
                == PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE)
            && $product->getIsSalable();
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" and "One time purchase" by product ids.
     * Retrieve array like product_id => boolean
     *
     * @param array $productPreset
     * @return array
     */
    public function isOneTimeAndSubscribePurchaseByIds(array $productPreset)
    {
        $result = [];
        $productIds = array_keys($productPreset);
        foreach ($this->getProductSubscriptionPurchaseTypeByIds($productIds) as $key => $element) {
            $result[$key] = false;
            if ($element[Attribute::SUBSCRIPTION_PURCHASE_TYPE] == PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE
                && $element['is_salable']) {
                $result[$key] = true;
            }
        }
        return $result;
    }

    /**
     * Return subscription purchase type.
     *
     * @param ProductInterface $product
     * @return int|null
     */
    private function getProductSubscriptionPurchaseType(ProductInterface $product)
    {
        if ($product->getTypeId() === Grouped::TYPE_CODE) {
            return $this->productTypeResolver->resolve($product->getTypeId())
                ->getProductDataObject($product)->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
        }
        return $product->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
    }

    /**
     * Return subscription purchase types array by product ids.
     *
     * @param array $productsIds
     * @return array
     */
    private function getProductSubscriptionPurchaseTypeByIds(array $productsIds)
    {
        $result = [];
        $productsCollection = $this->productCollectionFactory->create();
        $productsCollection->addAttributeToSelect(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
        $productsCollection->addFieldToFilter('entity_id', ['in' => $productsIds]);
        foreach ($productsCollection as $product) {
            $result[$product->getId()] = [
                'is_salable' => $product->getIsSalable(),
                Attribute::SUBSCRIPTION_PURCHASE_TYPE => $this->getProductSubscriptionPurchaseType($product)
            ];
        }
        return $result;
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getProductBillingFrequencies(ProductInterface $product)
    {
        $productId = $product->getId();
        $productBillingFrequencies = $this->frequencyOptionRepository
            ->getListByProductId($productId)
            ->getItems();

        return $productBillingFrequencies;
    }

    /**
     * Returns list of product billing frequencies by product id.
     *
     * @param int $productId
     * @return array
     */
    private function getProductBillingFrequenciesById($productId)
    {
        $productBillingFrequencies = $this->frequencyOptionRepository
            ->getListByProductId($productId)
            ->getItems();

        return $productBillingFrequencies;
    }

    /**
     * Get request
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    private function getRequest()
    {
        return $this->request;
    }

    /**
     * @param null $product
     * @param null $productId
     * @return bool
     */
    public function getCustomerGroupLimitation($product = null, $productId = null)
    {
        if ($productId) {
            try {
                $product = $this->productRepository->getById($productId);
            } catch (NoSuchEntityException $e) {
                $this->logger->log($e, \Psr\Log\LogLevel::DEBUG);
                return false;
            }
        }
        $websiteId = $product->getStore()->getWebsiteId() ? $product->getStore()->getWebsiteId() : null;
        if ($this->config->getAllowAllCustomerGroups($websiteId)) {
            $customerGroups = $this->config->getCustomerGroupLimit($websiteId);
            if ($customerGroups != null) {
                if (array_search(
                        $this->customerSession->getCustomer()->getGroupId(),
                        explode(',', $customerGroups)
                    ) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
}
