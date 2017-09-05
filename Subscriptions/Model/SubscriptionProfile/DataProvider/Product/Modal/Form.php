<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;
use TNW\Subscriptions\Model\Backend\Session\Quote as SessionQuote;

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
     * @var string
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
     * @var SessionQuote
     */
    protected $sessionQuote;

    /**
     * @var \Magento\Directory\Model\CurrencyFactory
     */
    protected $currencyFactory;

    /**
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
     * @param SessionQuote $sessionQuote
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
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
        SessionQuote $sessionQuote,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
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
     * Returns config fot start on field.
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
     * @return array|null|ProductBillingFrequencyInterface[]
     */
    private function getProductBillingFrequencies($productId = null)
    {
        if ($this->productBillingFrequencies === null) {
            $this->productBillingFrequencies = [];
            if (!$productId) {
                $productId = $this->request->getParam('product_id', null);
            }
            if ($productId) {
                $this->productBillingFrequencies = $this->recurringOptionRepository
                    ->getListByProductId($productId)
                    ->getItems();
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

        /** @var ProductBillingFrequencyInterface $productFrequency */
        foreach ($this->getProductBillingFrequencies($productId) as $productFrequency) {
            $frequency = $this->frequencyRepository->getById($productFrequency->getBillingFrequencyId());

            $result[] = [
                'label' => $frequency->getLabel(),
                'value' => $productFrequency->getBillingFrequencyId(),
            ];
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
        if ($this->trialPeriod === null) {
            if (!$productId) {
                $productId = (int)$this->request->getParam('product_id', 0);
            }
            $trialLength = 0;
            $trialUnit = 0;
            $trialPriceLabel = '';
            if ($productId) {
                $product = $this->productRepository->getById($productId);
                $show = (bool)$product->getCustomAttribute(
                    \TNW\Subscriptions\Model\Product\Attribute::SUBSCRIPTION_TRIAL_STATUS
                )->getValue();

                if ($show) {
                    $trialLength = $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH)
                        ? (int)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH)->getValue()
                        : 0;
                    $trialUnit = $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT)
                        ? (int)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT)->getValue()
                        : 0;
                    $trialUnit = $this->unitType->getLabelByValueAndLength($trialUnit, $trialLength);

                    $currencySymbol = $this->getCurrentCurrencySymbol();
                    $trialPriceLabel = (int)$product->getCustomAttribute(
                            Attribute::SUBSCRIPTION_TRIAL_PRICE
                        )->getValue() . $currencySymbol . ' ' . __('for') . ' ';
                }
            }
            $this->trialPeriod = $trialLength && $trialUnit ? $trialPriceLabel . $trialLength . ' ' . $trialUnit : '';
        }

        return $this->trialPeriod;
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

        return $this->priceCalculator->getUnitPrice($productId, $billingFrequencyId);
    }

    /**
     * Get currency symbol from session if exists here or from store manager.
     *
     * @return string
     */
    protected function getCurrentCurrencySymbol()
    {
        $currencyCode = $this->sessionQuote->getCurrencyId();

        if ($currencyCode) {
            /** @var \Magento\Directory\Model\Currency $currency */
            $currency = $this->currencyFactory->create()->load($currencyCode);
            $currencySymbol = $currency->getCurrencySymbol();
        } else {
            $currencySymbol = $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
        }

        return $currencySymbol;
    }
}
