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
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
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
    private $productRepository;

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
     * Form constructor.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ProductRepositoryInterface $productRepository
     * @param RecurringOptionRepository $repository
     * @param BillingFrequencyRepository $frequencyRepository
     * @param RequestInterface $request
     * @param TrialLengthUnitType $unitType
     * @param PriceCalculator $priceCalculator
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
                $data[self::FORM_DATA_VALUE]['billing_frequency_id'] = $frequency->getBillingFrequencyId();
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
            'billing_frequency' => [
                'children' => [
                    'billing_frequency_id' => [
                        'arguments' => [
                            'data' => [
                                'options' => $this->getProductBillingFrequenciesAsOptionArray(),
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
                                    'visible' => $this->showTrialPeriod(),
                                ]
                            ]
                        ]
                    ],
                    'price' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'component' => 'TNW_Subscriptions/js/components/add-product-form-price',
                                    'validation' => [
                                        'validate-zero-or-greater' => true,
                                    ],
                                    'imports' => [
                                        'changeValue' => 'index = billing_frequency_id:value',
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
     * @return array
     */
    private function getStartOnFieldConfig()
    {
        $visible = false;
        $value = null;

        $productId = $this->request->getParam('product_id', null);

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
     * @return array
     */
    private function getProductBillingFrequencies()
    {
        if ($this->productBillingFrequencies === null) {

            $this->productBillingFrequencies = [];

            $productId = $this->request->getParam('product_id', null);

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
     * @return array
     */
    public function getProductBillingFrequenciesAsOptionArray()
    {
        $result = [];

        /** @var ProductBillingFrequencyInterface $productFrequency */
        foreach ($this->getProductBillingFrequencies() as $productFrequency) {
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
     * @return \Magento\Framework\Phrase|string
     */
    private function getTrialPeriod()
    {
        if ($this->trialPeriod === null) {
            $productId = (int)$this->request->getParam('product_id', 0);
            $trialLength = 0;
            $trialUnit = 0;
            if ($productId) {
                $product = $this->productRepository->getById($productId);
                $trialLength = $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH)
                    ? (int)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH)->getValue()
                    : 0;
                $trialUnit = $product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT)
                    ? (int)$product->getCustomAttribute(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT)->getValue()
                    : 0;
                $trialUnit = $this->unitType->getLabelByValueAndLength($trialUnit, $trialLength);
            }
            $this->trialPeriod = $trialLength && $trialUnit ? $trialLength . ' ' . $trialUnit : '';
        }

        return $this->trialPeriod;
    }

    /**
     * Provide visibility status for "Trial Period" field.
     *
     * @return bool
     */
    private function showTrialPeriod()
    {
        return $this->getTrialPeriod() ? true : false;
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
}
