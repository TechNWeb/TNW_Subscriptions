<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Block\Product\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as FrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context as ContextModel;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Subscribe product block instance
 */
class Subscribe extends \Magento\Framework\View\Element\Template
{
    /**
     * Api Product repository
     * 
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Subscription module config
     * 
     * @var Config
     */
    private $config;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * Modal form for adding single product to subscription
     * 
     * @var FrequencyOptionRepository
     */
    private $frequencyOptionRepository;

    /**
     * Repository for retrieving billing frequencies.
     *
     * @var FrequencyRepository
     */
    private $frequencyRepository;

    /**
     * @var ContextModel
     */
    private $contextModel;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param Config $config
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param FrequencyRepository $frequencyRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        Config $config,
        FrequencyOptionRepository $frequencyOptionRepository,
        FrequencyRepository $frequencyRepository,
        ContextModel $contextModel,
        StockRegistryInterface $stockRegistry,
        array $data = []
    ) {
        $this->coreRegistry = $context->getRegistry();
        $this->productRepository = $productRepository;
        $this->config = $config;
        $this->frequencyOptionRepository = $frequencyOptionRepository;
        $this->frequencyRepository = $frequencyRepository;
        $this->contextModel = $contextModel;
        $this->stockRegistry = $stockRegistry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current product model
     *
     * @return ProductInterface|Product
     */
    public function getProduct()
    {
        if (!$this->coreRegistry->registry('product')) {
            $productId = $this->getRequest()->getParam('id');
            $product = $this->productRepository->getById($productId);
            $this->coreRegistry->register('product', $product);
        }
        return $this->coreRegistry->registry('product');
    }

    /**
     * Get subscribe url
     *
     * @return string
     */
    public function getSubscribeUrl()
    {
        return $this->_urlBuilder->getUrl(
            'tnw_subscriptions/cart/add',
            ['product_id' => $this->getProduct()->getId()]
        );
    }

    /**
     * Get "Enable Subscriptions" config value for current website
     *
     * @return bool
     */
    public function isSubscribeAvailable()
    {
        return $this->config->isSubscriptionsActiveCurrent() && count($this->getProductBillingFrequencies());
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only.
     * 
     * @return bool
     */
    public function IsOnlySubscribePurchase()
    {
        return ($this->getProductSubscriptionPurchaseType() == PurchaseType::RECURRING_PURCHASE_TYPE);
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" and "One time purchase".
     *
     * @return bool
     */
    public function IsOneTimeAndSubscribePurchase()
    {
        return ($this->getProductSubscriptionPurchaseType() == PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE);
    }

    /**
     * Returns product billing frequencies as array.
     * 
     * @return array
     */
    public function getFrequencyOptions()
    {
        $result = [];
        $product = $this->getProduct();
        $productPrice = (int)$product->getPrice();

        /** @var ProductBillingFrequencyInterface $productFrequency */
        foreach ($this->getProductBillingFrequencies() as $productFrequency) {
            $frequency = $this->frequencyRepository->getById($productFrequency->getBillingFrequencyId());
            $label = $frequency->getLabel();
            $frequencyPrice = (int)$productFrequency->getPrice();

            if ($productPrice > $frequencyPrice) {
                $savings = $this->formatPrice($productPrice - $frequencyPrice);
                $label .= '  '. sprintf(__('(SAVE %s)'), $savings) ;
            }

             $data = [
                'label' => $label,
                'value' => $productFrequency->getBillingFrequencyId(),
                'is_default' => $productFrequency->getDefaultBillingFrequency(),
            ];

            if (!$this->getAllowEditSubscribeQty()) {
                $data['preset_qty'] = $productFrequency->getPresetQty();
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @return array
     */
    private function getProductBillingFrequencies()
    {
        if (!$this->hasData('product_billing_frequencies')) {
            $productId = $this->getProduct()->getId();
            $productBillingFrequencies = $this->frequencyOptionRepository
                ->getListByProductId($productId)
                ->getItems();
            $this->setData('product_billing_frequencies', $productBillingFrequencies);
        }

        return $this->getData('product_billing_frequencies');
    }

    /**
     * Can allow edit Subscribe Qty
     *
     * @return bool
     */
    public function getAllowEditSubscribeQty()
    {
        $product = $this->getProduct();
        return !(bool)$product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
    }

    /**
     * Get default value for Subscribe Qty
     *
     * @return int
     */
    public function getDefaultSubscribeQty()
    {
        if (!$this->getAllowEditSubscribeQty()) {
            foreach ($this->getFrequencyOptions() as $option) {
                if ($option['is_default'] && isset($option['preset_qty'])) {
                    return $option['preset_qty'] * 1;
                }
            }
        }
        return 1;
    }

    /**
     * Get is need check until canceled by default
     * 
     * @return string
     */
    public function getDefaultUntilCancelled()
    {
        return $this->config->isUntilCanceledChecked();
    }

    /**
     * Get default period value
     * 
     * @return string
     */
    public function getDefaultPeriod()
    {
        $period = \TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form::DEFAULT_PERIOD_VALUE;
        return $period ? (string)$period : '';
    }

    /**
     * 
     * 
     * @return bool
     */
    public function getIsVisibleStartOn()
    {
        $product = $this->getProduct();
        // Note: If product "is trial" then "start on" is start date of trial period,
        // otherwise "start on" is start date of subscription
        if ($product->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS)) {
            return $product->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE) == StartDateType::DEFINED_BY_CUSTOMER;
        } else {
            return $product->getData(Attribute::SUBSCRIPTION_START_DATE) == StartDateType::DEFINED_BY_CUSTOMER;
        }
    }

    /**
     * Get default value for Start on
     * 
     * @return string
     */
    public function getDefaultStartOn()
    {
        return $this->_localeDate->formatDate(null, \IntlDateFormatter::SHORT);
    }

    /**
     * Get min value for Start on
     *
     * @return string
     */
    public function getMinStartOn()
    {
        return $this->_localeDate->formatDate(null, \IntlDateFormatter::SHORT);
    }

    /**
     * Get input date format
     * 
     * @return string
     */
    public function getDateFormat()
    {
        return $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
    }

    /**
     * Format price according to locale settings.
     *
     * @param $price
     * @return float
     */
    private function formatPrice($price)
    {
        $currentStore = $this->_storeManager->getStore();

        return $this->contextModel->getPriceCurrency()->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $currentStore->getId(),
            $currentStore->getCurrentCurrencyCode()
        );
    }

    private function getProductSubscriptionPurchaseType()
    {
        return $this->getProduct()->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
    }

    /**
     * Returns validators for qty field. Depends on product settings
     *
     * @return array
     */
    public function getQtyValidators()
    {
        $params = [];
        $validators = [];
        $validators['required-number'] = true;

        /** @var \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem(
            $this->getProduct()->getId(),
            $this->getProduct()->getStore()->getWebsiteId()
        );

        $params['minAllowed']  = max((float)$stockItem->getQtyMinAllowed(), 1);
        if ($stockItem->getQtyMaxAllowed()) {
            $params['maxAllowed'] = $stockItem->getQtyMaxAllowed();
        }
        if ($stockItem->getQtyIncrements() > 0) {
            $params['qtyIncrements'] = (float)$stockItem->getQtyIncrements();
        }
        $validators['validate-item-quantity'] = $params;

        return $validators;
    }
}
