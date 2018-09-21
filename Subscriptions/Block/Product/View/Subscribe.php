<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Block\Product\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as FrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\ProductBillingFrequency\SavingsCalculation;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;

/**
 * Subscribe product block instance
 */
class Subscribe extends View
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
     * Repository for retrieving billing frequencies.
     *
     * @var FrequencyRepository
     */
    private $frequencyRepository;

    /**
     * Subscription Product View Config model.
     *
     * @var SubscriptionProductView
     */
    private $subscriptionProductViewConfig;

    /**
     * Savings calculation manager.
     *
     * @var SavingsCalculation
     */
    private $savingsCalculation;

    /**
     * Product type resolver.
     *
     * @var ProductTypeManagerResolver
     */
    private $subscriptionTypeResolver;

    /**
     * Subscription price calculator.
     *
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param SubscriptionProductView $subscriptionProductViewConfig
     * @param Config $config
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param FrequencyRepository $frequencyRepository
     * @param SavingsCalculation $savingsCalculation
     * @param ProductTypeManagerResolver $subscriptionTypeResolver
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        SubscriptionProductView $subscriptionProductViewConfig,
        Config $config,
        FrequencyOptionRepository $frequencyOptionRepository,
        FrequencyRepository $frequencyRepository,
        SavingsCalculation $savingsCalculation,
        ProductTypeManagerResolver $subscriptionTypeResolver,
        PriceCalculator $priceCalculator,
        array $data = []
    ) {
        $this->subscriptionProductViewConfig = $subscriptionProductViewConfig;
        $this->config = $config;
        $this->frequencyOptionRepository = $frequencyOptionRepository;
        $this->frequencyRepository = $frequencyRepository;
        $this->savingsCalculation = $savingsCalculation;
        $this->subscriptionTypeResolver = $subscriptionTypeResolver;
        $this->priceCalculator = $priceCalculator;
        parent::__construct($context, $urlEncoder, $jsonEncoder, $string, $productHelper, $productTypeConfig,
            $localeFormat, $customerSession, $productRepository, $priceCurrency, $data);
    }

    /**
     * Retrieve current product model.
     *
     * @return ProductInterface
     */
    public function getProduct()
    {
        if (!$this->_coreRegistry->registry('product')) {
            $productId = $this->getRequest()->getParam('id');
            $product = $this->productRepository->getById($productId);
            $this->_coreRegistry->register('product', $product);
        }
        return $this->_coreRegistry->registry('product');
    }

    /**
     * Retrieve old quote item id
     *
     * @return mixed
     */
    private function getOldQuoteItemId()
    {
        return $this->_coreRegistry->registry('old_quote_item_id');
    }

    /**
     * Get subscribe url.
     *
     * @return string
     */
    public function getSubscribeUrl()
    {
        $oldQuoteItemId = $this->getOldQuoteItemId();
        $params = ['product_id' => $this->getProduct()->getId()];
        if ($oldQuoteItemId) {
            $params['old_quote_item_id'] = $oldQuoteItemId;
        }
        return $this->_urlBuilder->getUrl(
            'tnw_subscriptions/cart/add',
            $params
        );
    }

    /**
     * Get "Enable Subscriptions" config value for current website.
     *
     * @return bool
     */
    public function isSubscribeAvailable()
    {
        return $this->subscriptionProductViewConfig->isSubscribeAvailable($this->getProduct());

    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only.
     *
     * @return bool
     */
    public function IsOnlySubscribePurchase()
    {
        return $this->subscriptionProductViewConfig->isOnlySubscribePurchase($this->getProduct());
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" and "One time purchase".
     *
     * @return bool
     */
    public function IsOneTimeAndSubscribePurchase()
    {
        return $this->subscriptionProductViewConfig->IsOneTimeAndSubscribePurchase($this->getProduct());
    }

    /**
     * Returns product billing frequencies as array.
     *
     * @return array
     */
    public function getFrequencyOptions()
    {
        $result = [];
        /** @var ProductBillingFrequencyInterface $productFrequency */
        foreach ($this->getProductBillingFrequencies() as $productFrequency) {
            $frequency = $this->frequencyRepository->getById($productFrequency->getBillingFrequencyId());
            $label = $frequency->getLabel();
            $data = [
                'label' => $label,
                'value' => $productFrequency->getBillingFrequencyId(),
                'frequency_unit' => $frequency->getFrequency(),
                'frequency_unit_type' => $frequency->getUnit(),
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
     * Returns product savings calculation type.
     *
     * @return int
     */
    public function getSavingCalculationType()
    {
        return $this->savingsCalculation->getSavingsCalculationType($this->getProduct());
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
        return $this->getIsInfiniteSubscriptions() ?: $this->config->isUntilCanceledChecked();
    }

    /**
     * Get is only infinite subscriptions available.
     *
     * @return string
     */
    public function getIsInfiniteSubscriptions()
    {
        return $this->getProduct()->getData(Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS);
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

        $params['minAllowed'] = max((float)$stockItem->getQtyMinAllowed(), 1);
        if ($stockItem->getQtyMaxAllowed()) {
            $params['maxAllowed'] = $stockItem->getQtyMaxAllowed();
        }
        if ($stockItem->getQtyIncrements() > 0) {
            $params['qtyIncrements'] = (float)$stockItem->getQtyIncrements();
        }
        $validators['validate-item-quantity'] = $params;

        return $validators;
    }

    /**
     * Returns product data array to display subscription form
     *
     * @return string
     */
    public function getProductDataArray()
    {
        $type = $this->getProduct()->getTypeId();
        $subsProductType = $this->subscriptionTypeResolver->resolve($type);

        $result = [
            'type' => $type,
            'product_price' => $this->getProduct()->getFinalPrice(),
            'frequency_data' => $this->getFrequencyPricesByProduct(
                $subsProductType->getProductDataObject($this->getProduct())
            ),
        ];

        switch ($type) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $childProducts = $this->getProduct()
                    ->getTypeInstance()
                    ->getSalableUsedProducts($this->getProduct(), null);
                $childArray = [];

                foreach ($childProducts as $childProduct) {
                    $childArray[$childProduct->getId()]['product_price'] = $childProduct->getFinalPrice();
                    $productDataObject = $subsProductType->getProductDataObject(
                        $this->getProduct(),
                        ['child_product' => $childProduct]
                    );
                    $childArray[$childProduct->getId()]['frequency_data'] = $this->getFrequencyPricesByProduct(
                        $productDataObject
                    );
                }

                $result['children'] = $childArray;
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $type));
                break;
        }

        return $this->_jsonEncoder->encode($result);
    }

    /**
     * Returns array of product dilling frequency prices.
     *
     * @param DataObject $productDataObject
     * @return array
     */
    protected function getFrequencyPricesByProduct(DataObject $productDataObject)
    {
        $result = [];
        $productBillingFrequencies = $this->frequencyOptionRepository
            ->getListByProductId($productDataObject->getId())
            ->getItems();
        foreach ($productBillingFrequencies as $productFrequency) {
            $frequencyPrice = $this->priceCalculator->getUnitPrice(
                $productDataObject,
                $productFrequency->getBillingFrequencyId()
            );
            $result[$productFrequency->getBillingFrequencyId()] = $frequencyPrice;
        }

        return $result;
    }
}
