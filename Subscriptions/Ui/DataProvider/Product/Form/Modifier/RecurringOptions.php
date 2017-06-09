<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Modal;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Boolean;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;

/**
 * Data provider for "Recurring Options" panel
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecurringOptions extends AbstractModifier
{
    /**#@+
     * Group values
     */
    const GROUP_RECURRING_OPTIONS_NAME = 'subscription-options';
    const GROUP_RECURRING_OPTIONS_SCOPE = 'data.product';
    const GROUP_RECURRING_OPTIONS_PREVIOUS_NAME = 'search-engine-optimization';
    const GROUP_RECURRING_OPTIONS_DEFAULT_SORT_ORDER = 31;
    /**#@-*/

    /**#@+
     * Button values
     */
    const BUTTON_ADD = 'button_add';
    /**#@-*/

    /**#@+
     * Container values
     */
    const CONTAINER_HEADER_NAME = 'container_header';
    const CONTAINER_OPTION = 'container_option';
    const CONTAINER_COMMON_NAME = 'container_common';
    const CONTAINER_TYPE_STATIC_NAME = 'container_type_static';
    /**#@-*/

    /**#@+
     * Grid values
     */
    const GRID_OPTIONS_NAME = 'recurring_options';//TODO wtf?
    const GRID_TYPE_SELECT_NAME = 'values';
    /**#@-*/

    /**#@+
     * Field values
     */
    const FIELD_ENABLE = 'affect_product_recurring_options';
    const FIELD_PRODUCT_BILLING_FREQUENCY_ID = 'id';
    const FIELD_BILLING_FREQUENCY_NAME = 'billing_frequency_id';
    const FIELD_IS_DEFAULT_NAME = 'default_billing_frequency';
    const FIELD_SORT_ORDER_NAME = 'sort_order';
    const FIELD_PRICE_NAME = 'price';
    const FIELD_INITIAL_FEE_NAME = 'initial_fee';
    const FIELD_IS_DELETE = 'is_delete';
    const FIELD_TITLE_NAME = 'title';
    /**#@-*/

    /**#@+
     * Import options values
     */
    const RECURRING_OPTIONS_LISTING = 'tnw_product_billingfrequency_index'; //todo product_recurring_options_listing
    /**#@-*/

    /**
     * @var \Magento\Catalog\Model\Locator\LocatorInterface
     */
    protected $locator;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @var BillingFrequencyRepository
     */
    private $billingFrequencyRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * RecurringOptions constructor.
     * @param LocatorInterface $locator
     * @param StoreManagerInterface $storeManager
     * @param ArrayManager $arrayManager
     * @param BillingFrequencyRepository $billingFrequencyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        LocatorInterface $locator,
        StoreManagerInterface $storeManager,
        ArrayManager $arrayManager,
        BillingFrequencyRepository $billingFrequencyRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->locator = $locator;
        $this->storeManager = $storeManager;
        $this->arrayManager = $arrayManager;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $options = [];
        $productOptions = $this->locator->getProduct()->getRecurringOptions() ?: [];

        $productId = $this->locator->getProduct()->getId();

        /** @var ProductBillingFrequencyInterface $option */
        foreach ($productOptions as $option) {
            $options[] = $this->formatPriceByPath(static::FIELD_PRICE_NAME, $option->getData());
        }

        $data =  array_replace_recursive(
            $data,
            [
                $productId => [
                    static::DATA_SOURCE_DEFAULT => [
                        static::FIELD_ENABLE => 1,
                        static::GRID_OPTIONS_NAME => $options
                    ]
                ]
            ]
        );

        return $data;
    }

    /**
     * Format float number to have two digits after delimiter
     *
     * @param string $path
     * @param array $data
     * @return array
     */
    protected function formatPriceByPath($path, array $data)
    {
        $value = $this->arrayManager->get($path, $data);

        if (is_numeric($value)) {
            $data = $this->arrayManager->replace($path, $data, $this->formatPrice($value));
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;

        $this->createRecurringOptionsPanel();

        return $this->meta;
    }

    /**
     * Create "Recurring Options" panel
     *
     * @return $this
     */
    protected function createRecurringOptionsPanel()
    {

        $this->meta = array_merge_recursive(
            $this->meta,
            [
                static::GROUP_RECURRING_OPTIONS_NAME => [
                    'children' => [
                        static::CONTAINER_HEADER_NAME => $this->getHeaderContainerConfig(1010),
                        static::FIELD_ENABLE => $this->getEnableFieldConfig(1020),
                        static::GRID_OPTIONS_NAME => $this->getOptionsGridConfig(1030)
                    ]
                ]
            ]
        );


        return $this;
    }

    /**
     * Get config for header container
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getHeaderContainerConfig($sortOrder)
    {

        $content = __(
            'Recurring option allows the merchant to specify the billing frequency for the product along with other features'
        );

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => null,
                        'formElement' => Container::NAME,
                        'componentType' => Container::NAME,
                        'template' => 'ui/form/components/complex',
                        'sortOrder' => $sortOrder,
                        'content' => $content,
                    ],
                ],
            ],
            'children' => [
                static::BUTTON_ADD => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'title' => __('Add Recurring Option'),
                                'formElement' => Container::NAME,
                                'componentType' => Container::NAME,
                                'component' => 'Magento_Ui/js/form/components/button',
                                'sortOrder' => 20,
                                'actions' => [
                                    [
                                        'targetName' => 'ns = ${ $.ns }, index = ' . static::GRID_OPTIONS_NAME,
                                        'actionName' => 'processingAddChild',
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for the whole grid
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getOptionsGridConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'addButtonLabel' => __('Add Option'),
                        'componentType' => DynamicRows::NAME,
                        'component' => 'TNW_Subscriptions/js/components/dynamic-rows-import-recurring-options',
                        'template' => 'ui/dynamic-rows/templates/collapsible',
                        'additionalClasses' => 'admin__field-wide',
                        'deleteProperty' => static::FIELD_IS_DELETE,
                        'deleteValue' => '1',
                        'addButton' => false,
                        'renderDefaultRecord' => false,
                        'columnsHeader' => false,
                        'collapsibleHeader' => true,
                        'sortOrder' => $sortOrder,
                        'dataProvider' => static::RECURRING_OPTIONS_LISTING,
                        'imports' => ['insertData' => '${ $.provider }:${ $.dataProvider }'],
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'headerLabel' => __('New Option'),
                                'componentType' => Container::NAME,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'positionProvider' => static::CONTAINER_OPTION . '.' . static::FIELD_SORT_ORDER_NAME,
                                'isTemplate' => true,
                                'is_collection' => true,
                            ],
                        ],
                    ],
                    'children' => [
                        static::CONTAINER_OPTION => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Fieldset::NAME,
                                        'label' => null,
                                        'sortOrder' => 10,
                                        'opened' => true,
                                    ],
                                ],
                            ],
                            'children' => [
                                static::FIELD_SORT_ORDER_NAME => $this->getPositionFieldConfig(40),
                                static::CONTAINER_COMMON_NAME => $this->getCommonContainerConfig(10),
                            ]
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * Get config for hidden field responsible for enabling recurring options processing
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getEnableFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Field::NAME,
                        'componentType' => Input::NAME,
                        'dataScope' => static::FIELD_ENABLE,
                        'dataType' => Number::NAME,
                        'visible' => false,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for container with common fields for any type
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getCommonContainerConfig($sortOrder)
    {
        $commonContainer = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Container::NAME,
                        'formElement' => Container::NAME,
                        'component' => 'Magento_Ui/js/form/components/group',
                        'breakLine' => false,
                        'showLabel' => false,
                        'additionalClasses' => 'admin__field-group-columns admin__control-group-equal',
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
            'children' => [
                static::FIELD_PRODUCT_BILLING_FREQUENCY_ID => $this->getProductBillingFrequencyIdFieldConfig(10),
                static::FIELD_BILLING_FREQUENCY_NAME => $this->getBillingFrequencyFieldConfig(20),
                static::FIELD_PRICE_NAME => $this->getPriceFieldConfig(30),
                static::FIELD_INITIAL_FEE_NAME => $this->getInitialFeeFieldConfig(40),
                static::FIELD_IS_DEFAULT_NAME => $this->getIsDefaultFieldConfig(50),
                static::FIELD_TITLE_NAME => $this->getTitleFieldConfig(60),
            ]
        ];

        return $commonContainer;
    }


    /**
     * Get config for hidden id field
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getProductBillingFrequencyIdFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Input::NAME,
                        'componentType' => Field::NAME,
                        'dataScope' => static::FIELD_PRODUCT_BILLING_FREQUENCY_ID,
                        'sortOrder' => $sortOrder,
                        'visible' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for title
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getTitleFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Input::NAME,
                        'componentType' => Field::NAME,
                        'component'  => 'Magento_Catalog/component/static-type-input',
                        'dataScope' => static::FIELD_TITLE_NAME,
                        'sortOrder' => $sortOrder,
                        'visible' => false,
                    ],
                ],
            ],
        ];
    }


    /**
     * Get config for "Billing Frequencies" field
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getBillingFrequencyFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Billing Frequency'),
                        'componentType' => Field::NAME,
                        'formElement' => Select::NAME,
                        'component' => 'TNW_Subscriptions/js/recurring-options-type',
                        'elementTmpl' => 'ui/grid/filters/elements/ui-select',
                        'dataScope' => static::FIELD_BILLING_FREQUENCY_NAME,
                        'dataType' => Text::NAME,
                        'sortOrder' => $sortOrder,
                        'options' => $this->getBillingFrequencies(),
                        'disableLabel' => true,
                        'multiple' => false,
                        'selectedPlaceholders' => [
                            'defaultPlaceholder' => __('-- Please select --'),
                        ],
                        'validation' => [
                            'required-entry' => true
                        ],
                        'notice' => __('Recurring schedule to be picked by the end consumer'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for "Default" field
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getIsDefaultFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => Checkbox::NAME,
                        'componentType' => Field::NAME,
                        'component' => 'TNW_Subscriptions/js/components/recurring-checkbox',
                        'parentContainer' => static::CONTAINER_OPTION,
                        'parentSelections' => static::GRID_OPTIONS_NAME,
                        'dataType' => Boolean::NAME,
                        'label' => __('Default'),
                        'dataScope' => static::FIELD_IS_DEFAULT_NAME,
                        'prefer' => 'radio',
                        'value' => '0',
                        'sortOrder' => $sortOrder,
                        'valueMap' => [
                            'false' => '0',
                            'true' => '1'
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for hidden field used for sorting
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getPositionFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataScope' => static::FIELD_SORT_ORDER_NAME,
                        'dataType' => Number::NAME,
                        'visible' => false,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for "Price" field
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getPriceFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Price'),
                        'componentType' => Field::NAME,
                        'component' => 'TNW_Subscriptions/js/components/recurring-price',
                        'formElement' => Input::NAME,
                        'dataScope' => static::FIELD_PRICE_NAME,
                        'dataType' => Number::NAME,
                        'addbefore' => $this->getCurrencySymbol(),
                        'sortOrder' => $sortOrder,
                        'validation' => [
                            'validate-zero-or-greater' => true
                        ],
                        'imports' => [
                            'disabled' => 'ns = ${ $.ns }, index = ' . LockPrice::CODE_LOCK_PRICE . ':checked',
                            'changeCommentAndValue' => 'index = price:value',
                            'changeCommentLockPrice' => 'index = ' . LockPrice::CODE_LOCK_PRICE . ':checked',
                            'changeCommentOfferDiscount' => 'index = ' . Discount::CODE_FLAT_DISCOUNT . ':checked',
                            'changeCommentDiscountAmount' => 'index = ' . Discount::CODE_DISCOUNT_AMOUNT . ':value',
                            'changeCommentDiscountType' => 'index = ' . Discount::CODE_DISCOUNT_TYPE . ':value',
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * Get config for "Initial Fee" field
     *
     * @param int $sortOrder
     * @return array
     */
    protected function getInitialFeeFieldConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Initial Fee'),
                        'componentType' => Field::NAME,
                        'formElement' => Input::NAME,
                        'dataScope' => static::FIELD_INITIAL_FEE_NAME,
                        'dataType' => Number::NAME,
                        'addbefore' => $this->getCurrencySymbol(),
                        'sortOrder' => $sortOrder,
                        'validation' => [
                            'validate-zero-or-greater' => true
                        ],
                        'notice' => __('Fee chanrged once upon creation of the subscription. Leave blank if subscription has no initial fee.')
                    ],
                ],
            ],
        ];
    }


    /**
     * Get options for drop-down control with billing frequencies
     *
     * @return array
     */
    protected function getBillingFrequencies()
    {
        $options = [];

        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var BillingFrequencyInterface $item */
        foreach ($this->billingFrequencyRepository->getList($searchCriteria)->getItems() as $item) {
            $options[] = [
                'value' => $item->getId(),
                'label' => $item->getLabel(),
            ];
        }

        return $options;
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    protected function getCurrencySymbol()
    {
        return $this->storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
    }

    /**
     * The getter function to get the locale currency for real application code
     *
     * @return \Magento\Framework\Locale\CurrencyInterface
     *
     * @deprecated
     */
    private function getLocaleCurrency()
    {
        if ($this->localeCurrency === null) {
            $this->localeCurrency = \Magento\Framework\App\ObjectManager::getInstance()->get(CurrencyInterface::class);
        }
        return $this->localeCurrency;
    }

    /**
     * Format price according to the locale of the currency
     *
     * @param mixed $value
     * @return string
     */
    protected function formatPrice($value)
    {
        if (!is_numeric($value)) {
            return null;
        }

        $store = $this->storeManager->getStore();
        $currency = $this->getLocaleCurrency()->getCurrency($store->getBaseCurrencyCode());
        $value = $currency->toCurrency($value, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);

        return $value;
    }

}
