<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Directory\Model\Currency;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Modal form for adding single product to subscription.
 */
class Form extends AbstractDataProvider
{
    /**#@+
     * Form request values
     */
    const FORM_DATA_KEY = 'add_product_modal_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_create_add_product_modal_form';
    /**#@-*/

    /**#@+
     * Period field default value
     */
    const DEFAULT_PERIOD_VALUE = 1;
    /**#@-*/

    /**
     * Data scope component name.
     *
     * @var string
     */
    private $scopeName;

    /**
     * Repository for retrieving products.
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Repository for retrieving product billing frequencies.
     *
     * @var RecurringOptionRepository
     */
    private $recurringOptionRepository;

    /**
     * Help to retrieve data from request(GET, POST).
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Repository for retrieving billing frequencies.
     *
     * @var BillingFrequencyRepository
     */
    private $frequencyRepository;

    /**
     * Product billing frequencies cache.
     *
     * @var ProductBillingFrequencyInterface[]
     */
    private $productBillingFrequencies;

    /**
     * Help convert trial unit value into label.
     *
     * @var TrialLengthUnitType
     */
    private $unitType;

    /**
     * Trial period holder.
     *
     * @var array
     */
    private $trialPeriod;

    /**
     * Help calculate product price for billing frequency.
     *
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * Store Manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \TNW\Subscriptions\Model\Config
     */
    private $config;

    /**
     * @var QuoteSessionInterface
     */
    protected $sessionQuote;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var Currency
     */
    private $currentCurrency;

    /**
     * @var Context
     */
    protected $context;

    /**
     * Form constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProductRepositoryInterface $productRepository
     * @param RecurringOptionRepository $repository
     * @param BillingFrequencyRepository $frequencyRepository
     * @param RequestInterface $request
     * @param TrialLengthUnitType $unitType
     * @param PriceCalculator $priceCalculator
     * @param StoreManagerInterface $storeManager
     * @param \TNW\Subscriptions\Model\Config $config
     * @param QuoteSessionInterface $sessionQuote
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param Context $context
     * @param string $scope
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProductRepositoryInterface $productRepository,
        RecurringOptionRepository $repository,
        BillingFrequencyRepository $frequencyRepository,
        RequestInterface $request,
        TrialLengthUnitType $unitType,
        PriceCalculator $priceCalculator,
        StoreManagerInterface $storeManager,
        \TNW\Subscriptions\Model\Config $config,
        QuoteSessionInterface $sessionQuote,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        Context $context,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->recurringOptionRepository = $repository;
        $this->frequencyRepository = $frequencyRepository;
        $this->request = $request;
        $this->unitType = $unitType;
        $this->scopeName = $scope ? $scope : self::DATA_SCOPE_MODAL_FORM . '.' . self::DATA_SCOPE_MODAL_FORM;
        $this->priceCalculator = $priceCalculator;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->sessionQuote = $sessionQuote;
        $this->currencyFactory = $currencyFactory;
        $this->context = $context;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = [];

        /** @var ProductBillingFrequencyInterface $frequency */
        foreach ($this->getProductBillingFrequencies() as $frequency) {

            $billingFrequencyId = $frequency->getBillingFrequencyId();
            $billingFrequencyUnitPrice = $this->getBillingFrequencyUnitPrice($billingFrequencyId);
            $billingFrequencyPresetQty = $frequency->getPresetQty();
            if ($frequency->getDefaultBillingFrequency()) {
                $data[self::FORM_DATA_VALUE]['billing_frequency'] = $frequency->getBillingFrequencyId();
                $data[self::FORM_DATA_VALUE]['price'] = $billingFrequencyUnitPrice;
                $data[self::FORM_DATA_VALUE]['preset_qty'] = $billingFrequencyPresetQty;
            }
            $data[self::FORM_DATA_VALUE]['trial_period'] = $this->getTrialPeriod();
            $data[self::FORM_DATA_VALUE]['product_frequencies'][$billingFrequencyId]['price'] =
                $billingFrequencyUnitPrice;
            $data[self::FORM_DATA_VALUE]['product_frequencies'][$billingFrequencyId]['preset_qty'] =
                $billingFrequencyPresetQty;
            $data[self::FORM_DATA_VALUE]['product_frequencies'][$billingFrequencyId]['initial_fee'] =
                $this->getInitialFee($billingFrequencyId);
        }

        $data[self::FORM_DATA_VALUE]['period'] = self::DEFAULT_PERIOD_VALUE;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        $confData = parent::getConfigData();

        $confData = array_merge($confData, $this->getAdditionalConfig());

        return $confData;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            $this->getButtonsMetaData(),
            $this->getFieldsMetaData()
        );

        return $meta;
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {

    }

    /**
     * Returns billing frequency field set meta data.
     *
     * @return array
     */
    protected function getFieldsMetaData()
    {
        return $result = [
            'general' => [
                'children' => [
                    'billing_frequency' => [
                        'arguments' => [
                            'data' => [
                                'options' => $this->getProductBillingFrequenciesAsOptionArray(),
                            ],
                        ],
                    ],
                    'term' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'value' => $this->config->isUntilCanceledChecked(),
                                ],
                            ],
                        ],
                    ],
                    'period' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'imports' => [
                                        'visible' => '!ns = ${ $.ns }, index = term:checked',
                                    ],
                                ],
                            ],

                        ],
                    ],
                    'start_on' => [
                        'arguments' => [
                            'data' => [
                                'config' => $this->getStartOnFieldConfig(),
                            ],
                        ],
                    ],
                    'trial_period' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'visible' => $this->getTrialPeriod() ? true : false,
                                ]
                            ]
                        ]
                    ],
                    'initial_fee' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'priceFormat' => $this->getPriceFormatData(),
                                ]
                            ]
                        ]
                    ],
                    'price' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'addbefore' => $this->getCurrentCurrencySymbol(),
                                    'component' => 'TNW_Subscriptions/js/components/add-product-form-price',
                                    'validation' => [
                                        'validate-zero-or-greater' => true,
                                    ],
                                    'imports' => [
                                        'changeValue' => 'index = billing_frequency:value',
                                    ],
                                    'label' => $this->getTrialPeriod() ? __('Post trial price:') : __('Price') . ':',
                                    'priceFormat' => $this->getPriceFormatData(),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns buttons meta data.
     *
     * @return array
     */
    private function getButtonsMetaData()
    {
        return $result = [
            'add_to_subscription' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'container',
                            'componentType' => 'container',
                            'component' => 'Magento_Ui/js/form/components/button',
                            'title' => 'Add to Subscription',
                            'actions' => [
                                [
                                    'targetName' => $this->scopeName,
                                    'actionName' => 'ajaxSubmit',
                                ],
                            ],
                            'provider' => null,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns additional list of Ui component names.
     *
     * @return array
     */
    private function getAdditionalConfig()
    {
        return [
            'subProductListing' => Product::DATA_SCOPE_SUBSCRIPTION_LISTING,
            'insertForm' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM,
            'configurableModal' => 'configurableModal',
            'mainModal' => 'modal',
            'insertConfigurableForm' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM,
            'configurableForm' => ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM,
            'modalGrid' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID,
        ];
    }

    /**
     * Returns config for start on field.
     *
     * @param null|int $productId
     * @return array
     */
    protected function getStartOnFieldConfig($productId = null)
    {
        $visible = false;
        $value = null;
        if (!$productId) {
            $productId = $this->request->getParam('product_id', null);
        }
        if ($productId) {
            /** @var MagentoProduct $product */
            $product = $this->productRepository->getById($productId);

            //Note: If product "is trial" then "start on" is start date of trial period,
            // otherwise "start on" is start date of subscription
            if ($product->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS)) {
                if ($product->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE) == StartDateType::DEFINED_BY_CUSTOMER) {
                    $visible = true;
                } else {
                    $value = $product->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE);
                }
            } else {
                if ($product->getData(Attribute::SUBSCRIPTION_START_DATE) == StartDateType::DEFINED_BY_CUSTOMER) {
                    $visible = true;
                } else {
                    $value = $product->getData(Attribute::SUBSCRIPTION_START_DATE);
                }
            }
        }

        return [
            'visible' => $visible,
            'value' => $value,
        ];
    }

    /**
     * Returns list of product billing frequencies.
     *
     * @param null|int $productId
     * @return array|ProductBillingFrequencyInterface[]
     */
    private function getProductBillingFrequencies($productId = null)
    {
        if ($this->productBillingFrequencies === null) {
            $productId = $productId ?: $this->request->getParam('product_id');
            try {
                $this->productBillingFrequencies = $this->recurringOptionRepository
                    ->getListByProductId($productId)
                    ->getItems();
            } catch (\Exception $e) {
                $this->productBillingFrequencies = [];
                $this->context->log($e->getMessage());
            }
        }

        return $this->productBillingFrequencies;
    }

    /**
     * Returns product billing frequencies as array.
     *
     * @param null|int $productId
     * @return array
     */
    public function getProductBillingFrequenciesAsOptionArray($productId = null)
    {
        $result = [];
        $productId = $productId ?: $this->request->getParam('product_id');
        try {
            $product = $this->productRepository->getById($productId);
            $productPrice = (int)$product->getPrice();
            /** @var ProductBillingFrequencyInterface $productFrequency */
            foreach ($this->getProductBillingFrequencies($productId) as $productFrequency) {
                $frequency = $this->frequencyRepository->getById($productFrequency->getBillingFrequencyId());
                $label = $frequency->getLabel();
                if ($productId) {
                    $frequencyPrice = (int)$productFrequency->getPrice();
                    if ($productPrice > $frequencyPrice) {
                        $savings = $this->formatPrice($productPrice - $frequencyPrice);
                        $label .= '  '. sprintf(__('(SAVE %s)'), $savings) ;
                    }

                }
                $result[] = [
                    'label' => $label,
                    'value' => $productFrequency->getBillingFrequencyId(),
                ];
            }
        } catch (\Exception $e) {
            $this->context->log($e->getMessage());
        }

        return $result;
    }

    /**
     * Get trial period as string for product.
     *
     * @param null|int $productId
     * @return \Magento\Framework\Phrase|string
     */
    protected function getTrialPeriod($productId = null)
    {
        $productId = $productId ?: $this->request->getParam('product_id');
        if ($productId && $this->trialPeriod[$productId] === null) {
            $this->trialPeriod[$productId] = '';
            try {
                /** @var MagentoProduct $product */
                $product = $this->productRepository->getById($productId);
                $show = $this->getProductCustomAttribute(
                    $product,
                    Attribute::SUBSCRIPTION_TRIAL_STATUS,
                    false
                );
                if ($show) {
                    $trialLength = (int)$this->getProductCustomAttribute(
                        $product,
                        Attribute::SUBSCRIPTION_TRIAL_LENGTH,
                        0
                    );
                    $trialUnit = (int)$this->getProductCustomAttribute(
                        $product,
                        Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
                        0
                    );
                    $trialPrice = $this->getProductCustomAttribute(
                        $product,
                        Attribute::SUBSCRIPTION_TRIAL_PRICE,
                        0
                    );
                    $formattedPrice = $this->formatPrice($trialPrice);
                    $formattedTrialUnit = $this->unitType->getLabelByValueAndLength($trialUnit, $trialLength);
                    $trialPriceLabel = $formattedPrice . $this->getCurrentCurrencySymbol() . ' ' . __('for') . ' ';
                    if ($trialLength && $trialUnit) {
                        $this->trialPeriod[$productId] = $trialPriceLabel . $trialLength . ' ' . $formattedTrialUnit;
                    }
                }
            } catch (\Exception $e) {
                $this->context->log($e->getMessage());
            }
        }

        return $this->trialPeriod[$productId];
    }

    /**
     * Returns product custom attribute value or "default" value if attribute is not set.
     *
     * @param MagentoProduct $product
     * @param string $attributeCode
     * @param null|bool|int|string $default
     * @return null|bool|int|string
     */
    private function getProductCustomAttribute(MagentoProduct $product, $attributeCode, $default = null)
    {
        $result = $default;
        if ($product->getCustomAttribute($attributeCode)) {
            $result = $product->getCustomAttribute($attributeCode)->getValue();
        }

        return $result;
    }

    /**
     * Get calculated product price for billing frequency.
     *
     * @param string $billingFrequencyId
     * @return string
     */
    private function getBillingFrequencyUnitPrice($billingFrequencyId)
    {
        $productId = (int)$this->request->getParam('product_id', 0);

        return $this->priceCalculator->getUnitPrice($productId, $billingFrequencyId, null, false, false);
    }

    /**
     * Get currency symbol from session if exists here or from store manager.
     *
     * @return string
     */
    protected function getCurrentCurrencySymbol()
    {
        return $this->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * Get currency model for session currency_id if exists here or for base_currency from store manager.
     *
     * @return Currency
     */
    private function getCurrentCurrency()
    {
        if ($this->currentCurrency === null) {
            $currencyCode = $this->sessionQuote->getCurrencyId();
            if ($currencyCode) {
                $this->currentCurrency = $this->currencyFactory->create()->load($currencyCode);
            } else {
                $this->currentCurrency = $this->storeManager->getStore()->getBaseCurrency();
            }
        }

        return $this->currentCurrency;
    }

    /**
     * Format price according to locale settings.
     *
     * @param $price
     * @return float
     */
    private function formatPrice($price)
    {
        return $this->context->getPriceCurrency()->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->sessionQuote->getStoreId(),
            $this->getCurrentCurrency()
        );
    }

    /**
     * Returns price locale format data.
     *
     * @return string
     */
    private function getPriceFormatData()
    {
        $currencyCode = $this->getCurrentCurrency()->getCurrencyCode();

        return $this->context->getPriceFormatData($currencyCode);
    }

    /**
     * Returns initial fee for billing frequency and product.
     *
     * @param string $billingFrequencyId
     * @return float|int
     */
    private function getInitialFee($billingFrequencyId)
    {
        $productId = (int)$this->request->getParam('product_id', 0);
        $initialFee = $this->priceCalculator->getInitialFee($billingFrequencyId, $productId, true);

        return $initialFee ? $this->formatPrice($initialFee) : 0;
    }
}
