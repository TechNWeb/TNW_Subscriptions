<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as Configurable;
use Magento\Directory\Model\Currency;
use Magento\Framework\Api\Filter;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\Config\Source\StartDateType;
use TNW\Subscriptions\Model\Context as SubscriptionContext;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Modal form for adding single product to subscription.
 */
class Form extends AbstractDataProvider
{
    /**
     * Form request values.
     */
    const FORM_DATA_KEY = 'add_product_modal_form_data';
    const FORM_DATA_VALUE = 'new_subscription';

    /**
     * Form data scope.
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_create_add_product_modal_form';

    /**
     * Period field default value.
     */
    const DEFAULT_PERIOD_VALUE = 1;

    /**
     * Data scope component name.
     *
     * @var string
     */
    private $scopeName;

    /**
     * Product billing frequencies cache.
     *
     * @var array
     */
    private $productBillingFrequencies;

    /**
     * Trial period holder.
     *
     * @var array
     */
    protected $trialPeriod;

    /**
     * Help calculate product price for billing frequency.
     *
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * @var Currency
     */
    private $currentCurrency;

    /**
     * @var SubscriptionContext
     */
    protected $context;

    /**
     * Data providers form context.
     *
     * @var Context
     */
    protected $formContext;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * Subscriptions product manager.
     *
     * @var ProductTypeManagerResolver
     */
    protected $productTypeResolver;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PriceCalculator $priceCalculator
     * @param SubscriptionContext $context
     * @param Context $formContext
     * @param PoolInterface $pool
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param string $scope
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PriceCalculator $priceCalculator,
        SubscriptionContext $context,
        Context $formContext,
        PoolInterface $pool,
        ProductTypeManagerResolver $productTypeResolver,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->priceCalculator = $priceCalculator;
        $this->context = $context;
        $this->formContext = $formContext;
        $this->scopeName = $scope ? $scope : self::DATA_SCOPE_MODAL_FORM . '.' . self::DATA_SCOPE_MODAL_FORM;
        $this->trialPeriod = [];
        $this->productBillingFrequencies = [];
        $this->pool = $pool;
        $this->productTypeResolver = $productTypeResolver;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $productId = $this->getRequestProductId();
        $data[self::FORM_DATA_VALUE] = $this->getFrequenciesData(true, $productId, [
            'super_attribute' => $this->formContext->getRequest()->getParam('super_attribute'),
        ]);

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        return array_merge(parent::getConfigData(), $this->getAdditionalConfig());
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = array_merge_recursive(
            parent::getMeta(),
            $this->getButtonsMetaData(),
            $this->getFieldsMetaData()
        );

        /** @var ModifierInterface $modifier */
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

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
        return [
            'general' => [
                'children' => [
                    'billing_frequency' => [
                        'arguments' => [
                            'data' => [
                                'options' => $this->getProductBillingFrequenciesAsOptionArray(),
                                'config' => [
                                    'priceFormat' => $this->getPriceFormatData(),
                                    'addbefore' => $this->getCurrentCurrencySymbol(),
                                    'template' => 'TNW_Subscriptions/form/subscription-profile/checkbox-set',
                                    'currencySymbol' => $this->getCurrentCurrencySymbol(),
                                ],
                            ],
                        ],
                    ],
                    'term' => [
                        'arguments' => [
                            'data' => [
                                'config' => $this->getFieldTermConfig(),
                            ],
                        ],
                    ],
                    'period' => [
                        'arguments' => [
                            'data' => [
                                'config' => $this->getFieldPeriodConfig(),
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
        return [
            'add_to_subscription' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => 'container',
                            'componentType' => 'container',
                            'component' => 'TNW_Subscriptions/js/components/primary-button',
                            'template' => 'TNW_Subscriptions/form/element/primary-button',
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
            'mainModal' => 'addProductsModal',
            'insertConfigurableForm' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM,
            'configurableForm' => ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM,
            'modalGrid' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID,
        ];
    }

    /**
     * Returns config for start on field.
     *
     * @param null|int|string $productId
     * @return array
     */
    protected function getStartOnFieldConfig($productId = null)
    {
        $visible = false;
        $value = null;
        $productId = $productId ?: $this->getRequestProductId();
        if ($productId) {
            /** @var MagentoProduct $product */
            $product = $this->formContext->getProductRepository()->getById($productId);

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
     * @param null|int|string $productId
     * @return array|ProductBillingFrequencyInterface[]
     */
    protected function getProductBillingFrequencies($productId = null)
    {
        $productId = $productId ?: $this->getRequestProductId();
        if ($productId && !isset($this->productBillingFrequencies[$productId])) {
            $this->productBillingFrequencies[$productId] = [];
            try {
                $this->productBillingFrequencies[$productId] = $this->formContext
                    ->getRecurringOptionRepository()
                    ->getListByProductId($productId)
                    ->getItems();
            } catch (\Exception $e) {
                $this->context->log($e->getMessage());
            }
        }
        $return = !empty($this->productBillingFrequencies[$productId])
            ? $this->productBillingFrequencies[$productId]
            : [];

        return $return;
    }

    /**
     * Returns product billing frequencies as array.
     *
     * @param null|int|string $productId
     * @return array
     */
    public function getProductBillingFrequenciesAsOptionArray($productId = null)
    {
        $result = [];
        $productId = $productId ?: $this->getRequestProductId();
        try {
            /** @var ProductBillingFrequencyInterface $productFrequency */
            foreach ($this->getProductBillingFrequencies($productId) as $productFrequency) {
                $frequency = $this->formContext->getFrequencyRepository()
                    ->getById($productFrequency->getBillingFrequencyId());
                $label = $frequency->getLabel();
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
     * @param null|int|string $productId
     * @return \Magento\Framework\Phrase|string
     */
    protected function getTrialPeriod($productId = null)
    {
        $productId = $productId ?: $this->getRequestProductId();
        if ($productId && !isset($this->trialPeriod[$productId])) {
            $this->trialPeriod[$productId] = '';
            try {
                /** @var MagentoProduct $product */
                $product = $this->formContext->getProductRepository()->getById($productId);
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
                    $formattedPrice = $trialPrice
                        ? $this->formatPrice($this->convertPrice($trialPrice))
                        : __('Free');
                    $formattedTrialUnit = $this->formContext->getUnitType()
                        ->getLabelByValueAndLength($trialUnit, $trialLength);
                    $trialPriceLabel = $formattedPrice . ' ' . __('for') . ' ';
                    if ($trialLength && $trialUnit) {
                        $this->trialPeriod[$productId] = $trialPriceLabel . $trialLength . ' ' . $formattedTrialUnit;
                    }
                }
            } catch (\Exception $e) {
                $this->context->log($e->getMessage());
            }
        }

        $return = isset($this->trialPeriod[$productId]) ? $this->trialPeriod[$productId] : null;

        return $return;
    }

    /**
     * Returns product custom attribute value or "default" value if attribute is not set.
     *
     * @param MagentoProduct $product
     * @param string $attributeCode
     * @param null|bool|int|string $default
     * @return null|bool|int|string
     */
    protected function getProductCustomAttribute(MagentoProduct $product, $attributeCode, $default = null)
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
     * @param DataObject $productDataObject
     * @return string
     */
    private function getBillingFrequencyUnitPrice($billingFrequencyId, $productDataObject)
    {
        return $this->priceCalculator->getUnitPrice($productDataObject, $billingFrequencyId, null, false);
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
            $currencyCode = $this->formContext->getSession()->getCurrencyId();
            if ($currencyCode) {
                $this->currentCurrency = $this->formContext->getCurrencyFactory()
                    ->create()
                    ->load($currencyCode);
            } else {
                $this->currentCurrency = $this->formContext->getStoreManager()
                    ->getStore()
                    ->getBaseCurrency();
            }
        }

        return $this->currentCurrency;
    }

    /**
     * Format price according to locale settings.
     *
     * @param string|float $price
     * @return float
     */
    private function formatPrice($price)
    {
        return $this->context->getPriceCurrency()->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->formContext->getSession()->getStoreId(),
            $this->getCurrentCurrency()
        );
    }

    /**
     * Returns converted to currecy price.
     *
     * @param string|float $price
     * @return float
     */
    protected function convertPrice($price)
    {
        return $this->context->getPriceCurrency()->convert(
            $price,
            $this->formContext->getSession()->getStoreId(),
            $this->getCurrentCurrency()
        );
    }

    /**
     * Returns price locale format data.
     *
     * @return string
     */
    protected function getPriceFormatData()
    {
        $currencyCode = $this->getCurrentCurrency()->getCurrencyCode();

        return $this->context->getPriceFormatData($currencyCode);
    }

    /**
     * Returns initial fee for billing frequency and product.
     *
     * @param string $billingFrequencyId
     * @param int|string|null $productId
     * @return float|int
     */
    protected function getInitialFee($billingFrequencyId, $productId)
    {
        $initialFee = $this->getNotFormattedInitialFee($billingFrequencyId, $productId);

        return $initialFee ? $this->formatPrice($initialFee) : 0;
    }

    /**
     * Return billing frequency initial fee without formatting.
     *
     * @param int $billingFrequencyId
     * @param int $productId
     * @return float
     */
    protected function getNotFormattedInitialFee($billingFrequencyId, $productId)
    {
        $productId = $productId ?: $this->getRequestProductId();

        return $this->priceCalculator->getInitialFee($billingFrequencyId, $productId);
    }

    /**
     * Returns price for current product.
     *
     * @param int|string|null $productId
     * @param null|array $additionalData
     * @return string
     */
    private function getProductPrice($productId = null, $additionalData = null)
    {
        $productPrice = null;
        $productId = $productId ?: $this->getRequestProductId();
        if ($productId) {
            $product = $this->formContext->getProductRepository()->getById($productId);
            $price = $product->getPrice();
            if (!$price
                && $product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                && $additionalData && isset($additionalData['super_attribute'])
            ) {
                /** @var Configurable $typeInstance */
                $typeInstance = $product->getTypeInstance();
                $simpleProduct = $typeInstance
                    ->getProductByAttributes($additionalData['super_attribute'], $product);
                $price = $simpleProduct->getPrice();
            }
            $productPrice = $this->convertPrice($price);

        }

        return $productPrice;
    }

    /**
     * Returns price for current product.
     *
     * @param int|string|null $productId
     * @return string
     */
    protected function getSavingsCalculation($productId = null)
    {
        $result = null;
        $productId = $productId ?: $this->getRequestProductId();
        if ($productId) {
            $product = $this->formContext->getProductRepository()->getById($productId);
            $result = $this->getSavingsCalculationType($product);
        }

        return $result;
    }

    /**
     * Returns frequencies data for product.
     *
     * @param bool $needProductValues
     * @param string|null $productId
     * @param array|null $additionalData
     * @return array
     */
    protected function getFrequenciesData($needProductValues, $productId, array $additionalData = null)
    {
        $data = [];
        $addedDefault = false;
        $productId = $productId ?: $this->getRequestProductId();
        /** @var ProductBillingFrequencyInterface $frequency */
        foreach ($this->getProductBillingFrequencies($productId) as $frequency) {
            $billingFrequencyId = $frequency->getBillingFrequencyId();
            $additionalData['billing_frequency'] = $billingFrequencyId;
            $productDataObject = $this->getProductObjectData($productId, $additionalData);
            $data['product_frequencies'][$billingFrequencyId] =
                $this->getBillingFrequencyData($productDataObject, $frequency, $additionalData);
            if ($needProductValues && ($frequency->getDefaultBillingFrequency() || !$addedDefault)) {
                $data['billing_frequency'] = $billingFrequencyId;
                $data['price'] = $data['product_frequencies'][$billingFrequencyId]['price'];
                $data['preset_qty'] = $frequency->getPresetQty();
                $addedDefault = true;
            }
        }

        if ($needProductValues) {
            $data['trial_period'] = $this->getTrialPeriod($productId);
            $data['product_price'] = $this->getProductPrice($productId, $additionalData);
            $data['period'] = self::DEFAULT_PERIOD_VALUE;
            $data['savings_calculation'] = $this->getSavingsCalculation($productId);
        }

        return $data;
    }

    /**
     * Return product DataObject from productId.
     *
     * @param string $productId
     * @param array|null $additionalData
     * @return DataObject
     */
    private function getProductObjectData($productId, $additionalData = null)
    {
        $product = $this->formContext->getProductRepository()->getById($productId);

        return $this->productTypeResolver
            ->resolve($product->getTypeId())
            ->getProductDataObject($product, $additionalData);
    }
    /**
     * Returns product id from request.
     *
     * @return null|int|string
     */
    protected function getRequestProductId()
    {
        return $this->formContext->getRequest()->getParam('product_id');
    }

    /**
     * Returns billing frequency label.
     *
     * @param int|string $frequencyId
     * @return \Magento\Framework\Phrase|string
     */
    protected function getBillingFrequencyLabel($frequencyId)
    {
        try {
            $billingFrequencyLabel = $this->formContext->getFrequencyRepository()
                ->getById($frequencyId)
                ->getLabel();
        } catch (NoSuchEntityException $e) {
            $billingFrequencyLabel = __('Product was deleted');
        }
        return $billingFrequencyLabel;
    }

    /**
     * Return item billing frequency data.
     *
     * @param DataObject $productDataObject
     * @param ProductBillingFrequencyInterface $frequency
     * @return array
     */
    protected function getBillingFrequencyData(
        DataObject $productDataObject,
        ProductBillingFrequencyInterface $frequency
    ) {
        $billingFrequencyId = $frequency->getBillingFrequencyId();
        $result = [
            'price' => $this->getBillingFrequencyUnitPrice($billingFrequencyId, $productDataObject),
            'preset_qty' => $frequency->getPresetQty(),
            'initial_fee' => $this->getInitialFee($billingFrequencyId, $productDataObject->getChildProductId()),
        ];

        $frequencyData = [];
        $billingFrequency = $this->formContext->getFrequencyRepository()->getById($billingFrequencyId);

        if ($billingFrequency) {
            $frequencyData = [
                'frequency_unit' => $billingFrequency->getFrequency(),
                'frequency_unit_type' => $billingFrequency->getUnit(),
            ];
        }

        return array_merge($result, $frequencyData);
   }

    /**
     * Returns field term config.
     *
     * @param int|string|null $productId
     * @return array
     */
    protected function getFieldTermConfig($productId = null)
    {
        $productPrice = null;
        $productId = $productId ?: $this->getRequestProductId();
        $result = [
            'value' => $this->context->getConfig()->isUntilCanceledChecked(),
        ];

        if ($productId) {
            $product = $this->formContext->getProductRepository()->getById($productId);
            $isInfiniteSubscriptions = $product->getData(Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS);
            if ($isInfiniteSubscriptions) {
                $result['elementTmpl'] = 'TNW_Subscriptions/form/element/term-label';
                $result['value'] = 1;
            }
        }

        return $result;
    }

    /**
     * Returns field period config.
     *
     * @param int|string|null $productId
     * @return array
     */
    protected function getFieldPeriodConfig($productId = null)
    {
        $productPrice = null;
        $productId = $productId ?: $this->getRequestProductId();
        $result = [
            'imports' => [
                'visible' => '!ns = ${ $.ns }, index = term:checked',
            ],
        ];

        if ($productId) {
            $product = $this->formContext->getProductRepository()->getById($productId);
            $isInfiniteSubscriptions = $product->getData(Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS);
            if ($isInfiniteSubscriptions) {
                $result = ['visible' => false];
            }
        }

        return $result;
    }

    /**
     * Returns product savings calculation type.
     *
     * @param MagentoProduct $product
     * @return int
     */
    protected  function getSavingsCalculationType(MagentoProduct $product)
    {
        return $this->formContext->getSavingsCalculation()
            ->getSavingsCalculationType($product);
    }
}
