<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Api\Data\ProductInterface;
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
    const DEFAULT_PERIOD_VALUE = 2;

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
    protected $priceCalculator;

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
     * @var Currency
     */
    private $currentCurrency;

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
        $this->scopeName = $scope ? $scope : static::DATA_SCOPE_MODAL_FORM . '.' . static::DATA_SCOPE_MODAL_FORM;
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
        return $this;
    }

    /**
     * Returns additional list of Ui component names.
     *
     * @return array
     */
    public function getAdditionalConfig()
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
     * Returns product billing frequencies as array.
     *
     * @param null|int|string $productId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductBillingFrequenciesAsOptionArray($productId = null)
    {
        $result = [];
        $productId = $productId ?: $this->getRequestProductId();
        if (!$productId) {
            return $result;
        }

        $childProduct = $this->getChildProductFromRequest();
        $childProduct = $childProduct ?: $this->getChildProductFromCurrentItem();
        if ($childProduct) {
            foreach ($this->getProductBillingFrequencies($childProduct->getId()) as $childFrequency) {
                $childFrequencies[$childFrequency->getBillingFrequencyId()] = $childFrequency;
            }
        }
        try {
            /** @var ProductBillingFrequencyInterface $productFrequency */
            foreach ($this->getProductBillingFrequencies($productId) as $productFrequency) {
                if (!empty($childProduct)
                    && empty($childFrequencies[$productFrequency->getBillingFrequencyId()])
                ) {
                    //Child product has no such frequency set
                    continue;
                }
                $frequency = $this->formContext->getFrequencyRepository()
                    ->getById($productFrequency->getBillingFrequencyId());
                $label = $frequency->getLabel();
                $isDefault = !empty($childFrequencies[$productFrequency->getBillingFrequencyId()])
                    ? (bool)$childFrequencies[$productFrequency->getBillingFrequencyId()]->getDefaultBillingFrequency()
                    : (bool)$productFrequency->getDefaultBillingFrequency();
                if ($isDefault) {
                    $label = $label . ' ' . __('(most common)');
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
     * Returns billing frequency field set meta data.
     *
     * @return array
     * @throws NoSuchEntityException
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
                    'use_trial' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'visible' => $this->getCanSkipTrial(),
                                ]
                            ]
                        ]
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
                                    'disabled' => (bool) $this->getTrialPeriod(),
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
     * Returns config for start on field.
     *
     * @param null $productId
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getStartOnFieldConfig($productId = null)
    {
        $visible = false;
        $value = null;
        $productId = $productId ?: $this->getRequestProductId();
        if ($productId) {
            $arguments = [];
            $childProduct = $this->getChildProductFromRequest();
            $childProduct = $childProduct ?: $this->getChildProductFromCurrentItem();
            if ($childProduct) {
                $arguments['child_product'] = $childProduct;
            }

            $productData = $this->getProductObjectData($productId, $arguments);

            //Note: If product "is trial" then "start on" is start date of trial period,
            // otherwise "start on" is start date of subscription
            if ($productData->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS)) {
                if ($productData->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE)
                    == StartDateType::DEFINED_BY_CUSTOMER
                ) {
                    $visible = true;
                } else {
                    $value = $productData->getData(Attribute::SUBSCRIPTION_TRIAL_START_DATE);
                }
            } else {
                if ($productData->getData(Attribute::SUBSCRIPTION_START_DATE)
                    == StartDateType::DEFINED_BY_CUSTOMER
                ) {
                    $visible = true;
                } else {
                    $value = $productData->getData(Attribute::SUBSCRIPTION_START_DATE);
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
                $frequencies = $this->formContext
                    ->getRecurringOptionRepository()
                    ->getListByProductId($productId)
                    ->getItems();
                foreach ($frequencies as $frequency) {
                    if (!$frequency->getIsDisabled()) {
                        $this->productBillingFrequencies[$productId][] = $frequency;
                    }
                }
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
                $arguments = [];
                $childProduct = $this->getChildProductFromRequest();
                $childProduct = $childProduct ?: $this->getChildProductFromCurrentItem();
                if ($childProduct) {
                    $arguments['child_product'] = $childProduct;
                }
                $productData = $this->getProductObjectData($productId, $arguments);
                $show = $productData->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS);
                if ($show) {
                    $trialLength = (int)$productData->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH);
                    $trialUnit = (int)$productData->getData(Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT);
                    $trialPrice = $productData->getData(Attribute::SUBSCRIPTION_TRIAL_PRICE);
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
     * @param ProductInterface $product
     * @param string $attributeCode
     * @param null|bool|int|string $default
     * @return null|bool|int|string
     */
    protected function getProductCustomAttribute(ProductInterface $product, $attributeCode, $default = null)
    {
        $result = $default;
        if ($product->getCustomAttribute($attributeCode)) {
            $result = $product->getCustomAttribute($attributeCode)->getValue();
        }

        return $result;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getCurrentCurrencySymbol()
    {
        return $this->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * @param $price
     * @return float
     * @throws NoSuchEntityException
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
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getPriceFormatData()
    {
        $currencyCode = $this->getCurrentCurrency()->getCurrencyCode();

        return $this->context->getPriceFormatData($currencyCode);
    }

    /**
     * @param $billingFrequencyId
     * @param $productId
     * @return int|string
     * @throws NoSuchEntityException
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
     * @param null $productId
     * @return int|null
     * @throws NoSuchEntityException
     */
    protected function getSavingsCalculation($productId = null)
    {
        $result = null;
        $productId = $productId ?: $this->getRequestProductId();
        if ($productId) {
            $arguments = [];
            $childProduct = $this->getChildProductFromRequest();
            $childProduct = $childProduct ?: $this->getChildProductFromCurrentItem();
            if ($childProduct) {
                $arguments['child_product'] = $childProduct;
            }
            $productData = $this->getProductObjectData($productId, $arguments);
            $result = $this->getSavingsCalculationType($productData);
        }

        return $result;
    }

    /**
     * @param $needProductValues
     * @param $productId
     * @param array|null $additionalData
     * @return array
     * @throws NoSuchEntityException
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
            if ($productDataObject->getTypeId() === Configurable::TYPE_CODE
                && $productDataObject->getId() === $productDataObject->getChildProductId()
            ) {
                continue;
            }
            $productFrequencies = [];
            $childProductBillingFrequencies = $this
                ->getProductBillingFrequencies($productDataObject->getChildProductId());
            foreach ($childProductBillingFrequencies as $productFrequency) {
                $productFrequencies[$productFrequency->getBillingFrequencyId()] = $productFrequency;
            }
            if (!empty($productFrequencies[$billingFrequencyId])) {
                $data['product_frequencies'][$billingFrequencyId] =
                    $this->getBillingFrequencyData(
                        $productDataObject,
                        $productFrequencies[$billingFrequencyId]
                    );
            }
            if ($needProductValues
                && !empty($data['product_frequencies'][$billingFrequencyId])
                && ($frequency->getDefaultBillingFrequency() || !$addedDefault)
            ) {
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
     * @param $productId
     * @param null $additionalData
     * @return DataObject
     * @throws NoSuchEntityException
     */
    protected function getProductObjectData($productId, $additionalData = null)
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
     * @return ProductInterface|null
     * @throws NoSuchEntityException
     */
    protected function getChildProductFromRequest()
    {
        $productId = $this->getRequestProductId();
        if (!$productId) {
            return null;
        }
        $product = $this->formContext->getProductRepository()->getById($productId);
        $superAttribute = $this->formContext->getRequest()->getParam('super_attribute');
        if (!empty($product) && $product->getTypeId() === Configurable::TYPE_CODE && !empty($superAttribute)) {
            return $product->getTypeInstance()->getProductByAttributes($superAttribute, $product);
        }
        return null;
    }

    /**
     * Get child product from current subscription profile
     * @return ProductInterface|null
     * @throws NoSuchEntityException
     */
    protected function getChildProductFromCurrentItem()
    {
        if (!empty($this->currentItem)) {
            $children = $this->currentItem->getChildren();
            $child = is_array($children) ? reset($children) : null;
            if (!empty($child)) {
                $childProductId =  $child->getMagentoProductId() ?? $child->getProductId();
            }
            return !empty($childProductId)
                ? $this->formContext->getProductRepository()->getById($childProductId)
                : null;
        }
        return null;
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
     * @param DataObject $productDataObject
     * @param ProductBillingFrequencyInterface $frequency
     * @return array
     * @throws NoSuchEntityException
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
            'is_default' => (bool)($frequency->getDefaultBillingFrequency())
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
     * @throws NoSuchEntityException
     */
    protected function getFieldTermConfig($productId = null)
    {
        $productPrice = null;
        $productId = $productId ?: $this->getRequestProductId();
        $result = [
            'value' => $this->context->getConfig()->isUntilCanceledChecked(),
        ];

        if ($productId) {
            $arguments = [];
            $childProduct = $this->getChildProductFromRequest();
            $childProduct = $childProduct ?: $this->getChildProductFromCurrentItem();
            if ($childProduct) {
                $arguments['child_product'] = $childProduct;
            }
            $productData = $this->getProductObjectData($productId, $arguments);
            $isInfiniteSubscriptions = (bool)$productData->getData(Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS);
            if ($isInfiniteSubscriptions) {
                $result['disabled'] = true;
                $result['value'] = '1';
            }
        }

        return $result;
    }

    /**
     * @param null $productId
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getFieldPeriodConfig($productId = null)
    {
        $productPrice = null;
        $productId = $productId ?: $this->getRequestProductId();
        $result = [
            'imports' => [
                'onTermChange' => 'ns = ${ $.ns }, index = term:value',
                '__disableTmpl' => [
                    'onTermChange' => false
                ]
            ],
        ];

        if ($productId) {
            $arguments = [];
            $childProduct = $this->getChildProductFromRequest();
            $childProduct = $childProduct ?: $this->getChildProductFromCurrentItem();
            if ($childProduct) {
                $arguments['child_product'] = $childProduct;
            }
            $productData = $this->getProductObjectData($productId, $arguments);
            $isInfiniteSubscriptions = $productData->getData(Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS);
            if ($isInfiniteSubscriptions) {
                $result = ['visible' => false];
            }
        }

        return $result;
    }

    /**
     * Returns product savings calculation type.
     *
     * @param ProductInterface|DataObject $product
     * @return int
     */
    protected function getSavingsCalculationType($product)
    {
        return $this->formContext->getSavingsCalculation()
            ->getSavingsCalculationType($product);
    }

    /**
     * Get calculated product price for billing frequency.
     *
     * @param $billingFrequencyId
     * @param $productDataObject
     * @return string
     * @throws NoSuchEntityException
     */
    private function getBillingFrequencyUnitPrice($billingFrequencyId, $productDataObject)
    {
        return $this->priceCalculator->getUnitPrice($productDataObject, $billingFrequencyId, null, false);
    }

    /**
     * @return Currency
     * @throws NoSuchEntityException
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
     * @param $price
     * @return string
     * @throws NoSuchEntityException
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
     * @param null $productId
     * @param null $additionalData
     * @return float|null
     * @throws NoSuchEntityException
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
     * @param null $productId
     * @return bool|null
     */
    protected function getCanSkipTrial($productId = null)
    {
        $productId = $productId ?: $this->getRequestProductId();
            try {
                $arguments = [];
                $childProduct = $this->getChildProductFromRequest();
                $childProduct = $childProduct ?: $this->getChildProductFromCurrentItem();
                if ($childProduct) {
                    $arguments['child_product'] = $childProduct;
                }
                $productData = $this->getProductObjectData($productId, $arguments);
                $show = $productData->getData(Attribute::SUBSCRIPTION_TRIAL_STATUS);
                if ($show) {
                    $trialCanSkip = (bool)$productData->getData(Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP);
                }
            } catch (\Exception $e) {
                $this->context->log($e->getMessage());
            }

        return $trialCanSkip ?? null;
    }
}
