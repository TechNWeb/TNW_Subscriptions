<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Block\Product\View;

use IntlDateFormatter;
use InvalidArgumentException;
use Magento\Bundle\Model\Product\Type as TypeBundle;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Url\EncoderInterface as UrlEncoderInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Framework\Stdlib\StringUtils;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as FrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as FrequencyOptionRepository;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\ProductBillingFrequency\SavingsCalculation;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;
use TNW\Subscriptions\Model\Context as SubscriptionContext;

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
     * @var Config\Source\TrialLengthUnitType
     */
    private $trialLengthUnitType;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SubscriptionContext
     */
    protected $subscriptionContext;

    /**
     * @var Currency
     */
    private $currentCurrency;

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     * @param UrlEncoderInterface $urlEncoder
     * @param EncoderInterface $jsonEncoder
     * @param SerializerInterface $serializer
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param SubscriptionProductView $subscriptionProductViewConfig
     * @param Config $config
     * @param FrequencyOptionRepository $frequencyOptionRepository
     * @param FrequencyRepository $frequencyRepository
     * @param SavingsCalculation $savingsCalculation
     * @param ProductTypeManagerResolver $subscriptionTypeResolver
     * @param PriceCalculator $priceCalculator
     * @param Config\Source\TrialLengthUnitType $trialLengthUnitType
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlEncoderInterface $urlEncoder,
        EncoderInterface $jsonEncoder,
        SerializerInterface $serializer,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        SubscriptionProductView $subscriptionProductViewConfig,
        Config $config,
        FrequencyOptionRepository $frequencyOptionRepository,
        FrequencyRepository $frequencyRepository,
        SavingsCalculation $savingsCalculation,
        ProductTypeManagerResolver $subscriptionTypeResolver,
        PriceCalculator $priceCalculator,
        Config\Source\TrialLengthUnitType $trialLengthUnitType,
        SubscriptionContext $subscriptionContext,
        array $data = []
    ) {
        $this->subscriptionProductViewConfig = $subscriptionProductViewConfig;
        $this->config = $config;
        $this->frequencyOptionRepository = $frequencyOptionRepository;
        $this->frequencyRepository = $frequencyRepository;
        $this->savingsCalculation = $savingsCalculation;
        $this->subscriptionTypeResolver = $subscriptionTypeResolver;
        $this->priceCalculator = $priceCalculator;
        $this->trialLengthUnitType = $trialLengthUnitType;
        $this->subscriptionContext = $subscriptionContext;
        $this->context = $context;
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
        $this->serializer = $serializer;
    }

    /**
     * Retrieve current product model.
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct()
    {
        if ($this->getParentBlock() instanceof Renderer) {
            $productId = $this->getItem()->getProduct()->getId();
            $product = $this->productRepository->getById($productId);
            if (!$product->hasPreconfiguredValues() || $product->getQuoteItemId() !== $this->getItem()->getId()) {
                $buyRequest = $this->getItem()->getBuyRequest();
                $optionValues = $product->processBuyRequest($buyRequest);
                $product->setPreconfiguredValues($optionValues);
                $product->setQuoteItemId($this->getItem()->getId());
            }
            return $product;
        }
        if (!$this->_coreRegistry->registry('product')) {
            $productId = $this->getRequest()->getParam('id');
            $product = $this->productRepository->getById($productId);
            $this->_coreRegistry->register('product', $product);
        }
        return $this->_coreRegistry->registry('product');
    }

    /**
     * Get product data object. Respect inheritance config for configurable.
     * @return DataObject
     * @throws NoSuchEntityException
     */
    public function getProductDataObject()
    {
        if ($this->getParentBlock() instanceof Renderer) {
            $children = $this->getItem()->getChildren();
            $child = is_array($children) ? reset($children) : null;
            $arguments = !empty($child) ? ['child_product' => $child->getProduct()] : [];
            return $this->subscriptionTypeResolver->resolve($this->getItem()->getProduct()->getTypeId())
                ->getProductDataObject($this->getItem()->getProduct(), $arguments);
        }
        $typeId = $this->getProduct()->getTypeId();
        return $this->subscriptionTypeResolver->resolve($typeId)->getProductDataObject($this->getProduct());
    }

    /**
     * Get "Enable Subscriptions" config value for current website
     * and allowed customer groups to use subscription.
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isSubscribeAvailable()
    {
        if ($this->getProduct()->getTypeId() === TypeBundle::TYPE_CODE) {
            return false;
        }
        $websiteId = $this->getProduct()->getStore()->getWebsiteId()
            ? $this->getProduct()->getStore()->getWebsiteId()
            : null;
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
            return false;
        }

        return (
            $this->getPurchaseType() ===  PurchaseType::RECURRING_PURCHASE_TYPE
                || $this->getPurchaseType() === PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE
            )
            && $this->subscriptionProductViewConfig->isSubscribeAvailable($this->getProduct());
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only.
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isOnlySubscribePurchase()
    {
        if ($this->subscriptionProductViewConfig->isOneTimeAndSubscribePurchase($this->getProduct())
            && $this->getPurchaseType() === PurchaseType::RECURRING_PURCHASE_TYPE
        ) {
            return true;
        }
        return $this->subscriptionProductViewConfig->isOnlySubscribePurchase($this->getProduct());
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" and "One time purchase".
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isOneTimeAndSubscribePurchase()
    {
        return $this->getPurchaseType() ===  PurchaseType::ONE_TIME_AND_RECURRING_PURCHASE_TYPE
            && $this->subscriptionProductViewConfig->isOneTimeAndSubscribePurchase($this->getProduct());
    }

    /**
     * Get purchase type from product data object. Respect configurable inheritance.
     * @return int
     * @throws NoSuchEntityException
     */
    public function getPurchaseType()
    {
        return (int)$this->getProductDataObject()->getData(Attribute::SUBSCRIPTION_PURCHASE_TYPE);
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getOptions()
    {
        $options = [];
        $currentProduct = $this->getProduct();
        if ($currentProduct->getTypeId() === Configurable::TYPE_CODE) {
            $allowedProducts = $currentProduct->getTypeInstance()->getSalableUsedProducts($this->getProduct(), null);
            $configurableAttributes = $currentProduct->getTypeInstance()->getConfigurableAttributes($currentProduct);

            foreach ($allowedProducts as $product) {
                $productId = $product->getId();
                foreach ($configurableAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $product->getData($productAttribute->getAttributeCode());

                    $options[$productId][$productAttributeId] = $attributeValue;
                }
            }
        }

        $defaultValues = $this->getRequest()->getParam('attributes');

        if (!empty($defaultValues) && is_array($defaultValues)) {
            $options['defaultValues'] = $defaultValues;
        }

        return $options;
    }

    /**
     * Returns product billing frequencies as array.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getFrequencyOptions()
    {
        $result = [];
        /** @var ProductBillingFrequencyInterface $productFrequency */
        foreach ($this->getProductBillingFrequencies() as $productFrequency) {
            $frequency = $this->frequencyRepository->getById($productFrequency->getBillingFrequencyId());

            $preconfigured = $this->preconfiguredValue('subscription_data/unique/billing_frequency');
            $isDefault = null === $preconfigured
                ? $productFrequency->getDefaultBillingFrequency()
                : $preconfigured == $productFrequency->getBillingFrequencyId();

            $data = [
                'label' => $frequency->getLabel(),
                'value' => $productFrequency->getBillingFrequencyId(),
                'frequency_unit' => $frequency->getFrequency(),
                'frequency_unit_type' => $frequency->getUnit(),
                'is_default' => $isDefault,
            ];

            if (!$this->getAllowEditSubscribeQty()) {
                $data['preset_qty'] = $productFrequency->getPresetQty();
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * @param DataObject $productDataObject
     * @return array|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getProductBillingFrequenciesData(DataObject $productDataObject)
    {
        $result = [];
        $productId = !empty($productDataObject['child_product_id'])
            ? $productDataObject['child_product_id']
            : $productDataObject->getId();
        $productBillingFrequencies = $this->frequencyOptionRepository
            ->getListByProductId($productId)
            ->getItems();
        foreach ($productBillingFrequencies as $productBillingFrequency) {
            if (!$productBillingFrequency->getIsDisabled()) {
                $frequency = $this->frequencyRepository->getById($productBillingFrequency->getBillingFrequencyId());
                $frequencyPrice = $this->priceCalculator->getUnitPrice(
                    $productDataObject,
                    $productBillingFrequency->getBillingFrequencyId()
                );
                $data = [
                    'label' => $frequency->getLabel(),
                    'value' => $productBillingFrequency->getBillingFrequencyId(),
                    'frequency_unit' => $frequency->getFrequency(),
                    'frequency_unit_type' => $frequency->getUnit(),
                    'is_default' => $productBillingFrequency->getDefaultBillingFrequency(),
                    'price' => $frequencyPrice,
                    'preset_qty' => $productBillingFrequency->getPresetQty()
                ];
                $result[] = $data;
            }
        }
        return !empty($result) ? $result : null;
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @return array|ProductBillingFrequencyInterface[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getProductBillingFrequencies()
    {
        $frequencies = [];
        $productData = $this->getProductDataObject();
        $parentFrequencies = $this->frequencyOptionRepository
            ->getListByProductId($productData->getId())
            ->getItems();
        if ($productData->getChildProductId() && $productData->getId() !== $productData->getChildProductId()) {
            $childFrequencies = $this->frequencyOptionRepository
                ->getListByProductId($productData->getChildProductId())
                ->getItems();
            foreach ($parentFrequencies as $parentFrequency) {
                foreach ($childFrequencies as $childFrequency) {
                    if ($childFrequency->getBillingFrequencyId() === $parentFrequency->getBillingFrequencyId()
                        && !$childFrequency->getIsDisabled()
                    ) {
                        $frequencies[] = $childFrequency;
                    }
                }
            }
            return $frequencies;
        }
        foreach ($parentFrequencies as $parentFrequency) {
            if (!$parentFrequency->getIsDisabled()) {
                $frequencies[] = $parentFrequency;
            }
        }
        return $frequencies;
    }

    /**
     * Can allow edit Subscribe Qty
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getAllowEditSubscribeQty()
    {
        return !(bool)$this->getProductDataObject()->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
    }

    /**
     * Can allow display Subscribe Qty
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getAllowDisplaySubscribeQty()
    {
        $product = $this->getProduct();
        return !(bool) $product->getData(Attribute::SUBSCRIPTION_HIDE_QTY);
    }

    /**
     * Returns product savings calculation type.
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getSavingCalculationType()
    {
        return $this->savingsCalculation->getSavingsCalculationType($this->getProduct());
    }

    /**
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getDefaultFrequency()
    {
        return $this->preconfiguredValue('subscription_data/unique/billing_frequency');
    }

    /**
     * Get default value for Subscribe Qty
     *
     * @return float|int
     * @throws LocalizedException
     * @throws NoSuchEntityException
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

        return $this->preconfiguredValue('qty') ?: 1;
    }

    /**
     * Get is need check until canceled by default
     *
     * @return bool|mixed
     * @throws NoSuchEntityException
     */
    public function getDefaultUntilCancelled()
    {
        $term = $this->preconfiguredValue('subscription_data/unique/term') ?? $this->config->isUntilCanceledChecked();
        return $this->getIsInfiniteSubscriptions() ?: $term;
    }

    /**
     *  Get is only infinite subscriptions available.
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getIsInfiniteSubscriptions()
    {
        return $this->getProductDataObject()->getData(Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS);
    }

    /**
     * Get default period value
     *
     * @return int|string
     * @throws NoSuchEntityException
     */
    public function getDefaultPeriod()
    {
        $period = $this->preconfiguredValue('subscription_data/unique/period');

        return $period
            ? (string)$period
            : Form::DEFAULT_PERIOD_VALUE;
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getIsVisibleStartOn()
    {
        $product = $this->getProductDataObject();
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
     * @throws NoSuchEntityException
     */
    public function getDefaultStartOn()
    {
        $startOn = $this->preconfiguredValue('subscription_data/unique/start_on');

        return null !== $startOn
            ? (string)$startOn :
            $this->_localeDate->formatDate(null, IntlDateFormatter::SHORT);
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isSubscriptionDefault()
    {
        return (!$this->preconfiguredValue('qty') && !$this->preconfiguredValue('subscription_data'))
        || ($this->preconfiguredValue('qty') && $this->preconfiguredValue('subscription_data'));
    }

    /**
     * Get preconfigured options for configurable product
     *
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getPreconfiguredOptions()
    {
        $options = $this->preconfiguredValue('super_attribute');
        return null !== $options ? $options : null;
    }

    /**
     * Get preconfigured custom options for configurable product
     *
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getPreconfiguredCustomOptions()
    {
        $options = $this->preconfiguredValue('options');
        return null !== $options ? $options : null;
    }

    /**
     * Get min value for Start on
     *
     * @return string
     */
    public function getMinStartOn()
    {
        return $this->_localeDate->formatDate(null, IntlDateFormatter::SHORT);
    }

    /**
     * Get input date format
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->_localeDate->getDateFormat(IntlDateFormatter::SHORT);
    }

    /**
     * Returns validators for qty field. Depends on product settings
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getQtyValidators(ProductInterface $product)
    {
        $params = [];
        $validators = [];
        $validators['required-number'] = true;
        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProductDataArray()
    {
        $type = $this->getProduct()->getTypeId();
        $subsProductType = $this->subscriptionTypeResolver->resolve($type);
        $preconfiguredValues = $this->getProduct()->getPreconfiguredValues() ?? null;
        $productData = $subsProductType->getProductDataObject($this->getProduct());
        $result = [
            'type' => $type,
            'product' => [
                'product_price' => $this->getProduct()->getFinalPrice(),
                'frequency_data' => $this->getProductBillingFrequenciesData($productData),
                'trial_data' => $this->getTrialDataByProduct($productData),
                'recurring_settings' => $this->getRecurringSettingsByProduct($this->getProduct()),
                'qtyValidators' => $this->getQtyValidators($this->getProduct()),
                'preconfigured' => $preconfiguredValues
            ]
        ];

        switch ($type) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case Type::TYPE_DOWNLOADABLE:
                break;
            case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                $childProducts = $this->subscriptionTypeResolver
                    ->resolve($type)->getChildProducts($this->getProduct());
                $childArray = [];

                foreach ($childProducts as $childProduct) {
                    $productDataObject = $this->subscriptionTypeResolver
                        ->resolve($childProduct->getTypeId())->getProductDataObject($childProduct);
                    $childArray[$childProduct->getId()]['name'] = $childProduct->getName();
                    $childArray[$childProduct->getId()]['id'] = $childProduct->getId();
                    $childArray[$childProduct->getId()]['product_price'] = $childProduct->getFinalPrice();
                    $subscriptionPrice = trim($this->getProductPriceHtml(
                        $childProduct,
                        'subscription_price',
                        'grouped_view'
                    ));
                    if ($subscriptionPrice !== '') {
                        $childArray[$childProduct->getId()]['subscription_price'] = $this->serializer
                            ->unserialize($subscriptionPrice);
                    }
                    $childArray[$childProduct->getId()]['frequency_data'] = $this->getProductBillingFrequenciesData(
                        $productDataObject
                    );
                    $childArray[$childProduct->getId()]['trial_data'] = $this->getTrialDataByProduct(
                        $productDataObject
                    );
                    $childArray[$childProduct->getId()]['recurring_settings'] = $this->getRecurringSettingsByProduct(
                        $productDataObject
                    );
                    $childArray[$childProduct->getId()]['qtyValidators'] = $this->getQtyValidators($childProduct);
                }

                $associatedProducts = $this->getProduct()->getTypeInstance()
                    ->getAssociatedProducts($this->getProduct());
                foreach ($associatedProducts as $associatedProduct) {
                    $childArray[$associatedProduct->getId()]['qty'] = $associatedProduct->getQty();
                    $childArray[$associatedProduct->getId()]['is_salable'] = $associatedProduct->isSalable();
                }

                $result['children'] = $childArray;
                break;
            case Configurable::TYPE_CODE:
                $result['super_attributes'] = $this->getOptions();
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
                    $childArray[$childProduct->getId()]['frequency_data'] = $this->getProductBillingFrequenciesData(
                        $productDataObject
                    );
                    $childArray[$childProduct->getId()]['trial_data'] = $this->getTrialDataByProduct(
                        $productDataObject
                    );
                    $childArray[$childProduct->getId()]['recurring_settings'] = $this->getRecurringSettingsByProduct(
                        $productDataObject
                    );
                    $childArray[$childProduct->getId()]['qtyValidators'] = $this->getQtyValidators($childProduct);
                }

                $result['children'] = $childArray;
                break;
            default:
                throw new InvalidArgumentException(__('Unsupported product type -' . $type));
        }

        return $this->_jsonEncoder->encode($result);
    }

    /**
     * Returns array of recurring settings used on product page for child products
     *
     * @param $productData
     * @return array
     */
    protected function getRecurringSettingsByProduct($productData)
    {
        $attributesMap = [
            Attribute::SUBSCRIPTION_PURCHASE_TYPE,
            Attribute::SUBSCRIPTION_START_DATE,
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
            Attribute::SUBSCRIPTION_HIDE_QTY,
            Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
            Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS
        ];
        $result = [];
        foreach ($productData->getData() as $key => $attribute) {
            if (in_array($key, $attributesMap)) {
                if ($key == Attribute::SUBSCRIPTION_SAVINGS_CALCULATION) {
                    $result[substr($key, 11)] = $this->savingsCalculation
                        ->getSavingsCalculationType($productData);
                    continue;
                }
                $result[substr($key, 11)] = $attribute;
            }
        }
        return $result;
    }

    /**
     * Returns array of product billing frequency prices.
     *
     * @param DataObject $productDataObject
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
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

        return !empty($result) ? $result : null;
    }

    /**
     * Return trial data array if product has trial
     *
     * @param DataObject $productDataObject
     * @return array|null
     */
    protected function getTrialDataByProduct(DataObject $productDataObject)
    {
        if ($productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS)) {
            return [
                'trial_price' => $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_PRICE),
                'trial_can_skip' => $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP),
                'trial_length' => $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH),
                'trial_length_unit' => $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT),
                'trial_start_date' => $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE),
                'trial_label' => strtolower($this->trialLengthUnitType->getLabelByValueAndLength(
                    (int)$productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT),
                    $productDataObject->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH)
                ))
            ];
        }
        return null;
    }

    /**
     * Check if any preconfigured data
     *
     * @param $field
     * @return array|null
     * @throws NoSuchEntityException
     */
    private function preconfiguredValue($field)
    {
        return $this->getProduct()->hasPreconfiguredValues()
            ? $this->getProduct()->getPreconfiguredValues()->getData($field)
            : null;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAssociatedProducts()
    {
        $product = $this->getProduct();
        return $product->getTypeInstance()->getAssociatedProducts($product);
    }

    /**
     * Check is allowed add to cart
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isAllowedAddToCart()
    {
        return $this->subscriptionProductViewConfig->getCustomerGroupLimitation($this->getProduct());
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function getIsCanSkipTrial()
    {
        $product = $this->getProductDataObject();
        return $product->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS)
            && $product->getData(Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getIsTrial()
    {
        $preconfigured = $this->preconfiguredValue('subscription_data/unique/is_trial');
        return $this->_jsonEncoder->encode((int) $preconfigured);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getPriceFormatData()
    {
        $currencyCode = $this->getCurrentCurrency()->getCurrencyCode();

        return $this->subscriptionContext->getPriceFormatData($currencyCode);
    }

    /**
     * @return Currency
     * @throws NoSuchEntityException
     */
    private function getCurrentCurrency()
    {
        if ($this->currentCurrency === null) {
            $currencyCode = $this->context->getSession()->getCurrencyId();
            if ($currencyCode) {
                $this->currentCurrency = $this->context->getCurrencyFactory()
                    ->create()
                    ->load($currencyCode);
            } else {
                $this->currentCurrency = $this->context->getStoreManager()
                    ->getStore()
                    ->getBaseCurrency();
            }
        }

        return $this->currentCurrency;
    }
}
