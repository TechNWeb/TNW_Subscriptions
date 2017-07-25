<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Catalog\Helper\Image;
use Magento\Framework\Api\Filter;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Item;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Modal;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Backend\Session\Quote;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\Source\ShippingMethods;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Grid;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ConfigurableForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;
use TNW\Subscriptions\Model\Source\CurrencySelect;

class Product extends AbstractDataProvider
{
    /**#@+
     * Form scope and group values
     */
    const GROUP_SUBSCRIPTION_PROFILE_ADD_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    const DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    /**#@-*/

    /**#@+
     * Data scopes for child elements
     */
    const DATA_SCOPE_ADD_PRODUCT_MODAL_GRID = 'add_product_modal_grid';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM = 'add_product_modal_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM_BUTTON = 'add_to_subscription_button';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM = 'add_product_modal_configurable_form';
    /**#@-*/

    /**#@+
     * Layout handles for forms
     */
    const FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_create_add_product';
    const CONFIGURABLE_FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_create_add_product_configurable';
    /**#@-*/

    /**#@+
     * Subscription listing data scope
     */
    const DATA_SCOPE_SUBSCRIPTION_LISTING = 'tnw_subscriptionprofile_create_product_listing';
    /**#@-*/

    /**#@+
     * Subscription currency data scope
     */
    const GROUP_SUBSCRIPTION_PROFILE_CURRENCY_SELECT = 'tnw_subscriptionprofile_create_currency_select';
    /**#@-*/

    private $scopeName;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StepPool
     */
    private $stepPool;

    /**
     * @var Quote
     */
    private $session;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var BillingFrequencyRepository
     */
    private $frequencyRepository;

    /**
     * @var BillingFrequencyUnitType
     */
    private $frequencyUnitType;

    /**
     * @var ShippingMethods
     */
    private $shippingMethods;

    /**
     * @var DescriptionCreator
     */
    private $frequencyDescriptionCreator;

    /**
     * @var CurrencySelect
     */
    private $currencySelect;

    /**
     * Product constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param Quote $session
     * @param Image $imageHelper
     * @param Context $context
     * @param BillingFrequencyRepository $frequencyRepository
     * @param BillingFrequencyUnitType $frequencyUnitType
     * @param CurrencySelect $currencySelect
     * @param ShippingMethods $shippingMethods
     * @param DescriptionCreator $frequencyDescriptionCreator
     * @param array $meta
     * @param array $data
     * @param string $scopeName
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlInterface $urlBuilder,
        StepPool $stepPool,
        Quote $session,
        Image $imageHelper,
        Context $context,
        BillingFrequencyRepository $frequencyRepository,
        BillingFrequencyUnitType $frequencyUnitType,
        CurrencySelect $currencySelect,
        ShippingMethods $shippingMethods,
        DescriptionCreator $frequencyDescriptionCreator,
        array $meta = [],
        array $data = [],
        $scopeName = ''
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->session = $session;
        $this->imageHelper = $imageHelper;
        $this->context = $context;
        $this->frequencyUnitType = $frequencyUnitType;
        $this->frequencyRepository = $frequencyRepository;
        $this->currencySelect = $currencySelect;
        $this->shippingMethods = $shippingMethods;
        $this->frequencyDescriptionCreator = $frequencyDescriptionCreator;
        $this->scopeName = $scopeName ? $scopeName : self::DATA_SCOPE_SUBSCRIPTION_LISTING . '.' . self::DATA_SCOPE_SUBSCRIPTION_LISTING;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta,
            $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $items = $products = [];
        $estimatedPayment = 0;

        $subQuotes = $this->session->getSubQuotes();

        $counter = 1;
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $subscriptionData = null;
            $fullSubscriptionData = null;

            if (empty($subQuote->getAllItems())) {
                continue;
            }

            /** @var Item $item */
            foreach ($subQuote->getAllItems() as $item) {
                if (!$subscriptionData) {
                    $subscriptionData = $item->getBuyRequest()->getDataByPath(
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . DIRECTORY_SEPARATOR . Create::UNIQUE
                    );
                }
                if (!$fullSubscriptionData) {
                    $fullSubscriptionData = $item->getBuyRequest()->getDataByPath(
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
                    );
                } else {
                    $nonUniqueData = $item->getBuyRequest()->getDataByPath(
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . DIRECTORY_SEPARATOR . Create::NON_UNIQUE
                    );
                    $fullSubscriptionData[Create::NON_UNIQUE]['price'] +=
                        isset($nonUniqueData['price']) ? $nonUniqueData['price'] : 0;
                }


                $imageHelper = $this->imageHelper->init(
                    $item->getProduct(),
                    'product_listing_thumbnail'
                );

                $products[] = [
                    'thumbnail_alt' => $imageHelper->getLabel(),
                    'thumbnail_src' => $imageHelper->getUrl(),
                    'qty' => '(x' . $item->getQty() . ')',
                    'name' => $item->getName(),
                    'conf_options' => [], //TODO add here configurable options
                ];
            }

            $subTotal = $subQuote->getGrandTotal();

            $estimatedPayment += ((double)$subTotal * (int)$subscriptionData['period']);

            $items[] = [
                'title' => __('Subscription') . ' #' . $counter++,
                'products' => $products,
                'frequency_description' => $this->frequencyDescriptionCreator->getDescription(
                    $subQuote,
                    $fullSubscriptionData
                ),
                'shipping_method' => $this->getShippingMethodData($subQuote),
            ];

            $products = [];
        }

        $estimatedPayment = $this->formatPrice($estimatedPayment);

        return [
            'totalRecords' => count($items),
            'items' => $items,
            'estimatedPayment' => $estimatedPayment
        ];
    }

    /**
     * @param $price
     * @return float
     */
    protected function formatPrice($price)
    {
        return $this->context->getPriceCurrency()->format(
            $price,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->session->getStoreId(),
            $this->session->getCurrencyId()
        );
    }

    /**
     * @param ModelQuote $quote
     * @return array
     */
    protected function getShippingMethodData($quote)
    {
        $this->shippingMethods->setQuote($quote);
        $shippingMethods = [];

        $label = __('Selected on next step');

        if ($this->stepPool->getCurrentStep() === StepPool::STEP_PARAM_TYPE_REVIEW) {
            $label = $this->shippingMethods->getCurrentMethodLabel();
        } elseif ($this->stepPool->getCurrentStep() === StepPool::STEP_PARAM_TYPE_PAYMENT_BILLING) {
            $shippingMethods = $this->shippingMethods->getShippingMethodsAsOptionArray();
            $label = '';
        }

        return [
            'label' => $label,
            'methods' => $shippingMethods,
            'sub_quote_id' => $quote->getId(),
            'value' => $quote->getShippingAddress()->getShippingMethod()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            $this->getMetaData()
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
     * @return array
     */
    protected function getMetaData()
    {
        $result = [];

        if ($this->stepPool->getCurrentStep() !== StepPool::STEP_PARAM_TYPE_REVIEW) {
            $modalTarget = $this->scopeName . '.' . static::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS . '.modal';
            $result = [
                self::GROUP_SUBSCRIPTION_PROFILE_CURRENCY_SELECT => [
                    'children' => [
                        'currency_id' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'options' => $this->currencySelect->getAllOptions(),
                                        'value' => $this->currencySelect->getSelectedCurrencyId(),
                                        'formElement' => Select::NAME,
                                        'componentType' => Select::NAME,
                                        'label' => 'Order Currency:',
                                        'source' => 'SubscriptionProfile',
                                        'template' => 'TNW_Subscriptions/form/element/select',
                                        'data_form_part' => 'tnw_subscriptionprofile_create_account_form',
                                        'dataScope' => '$data.account.currency_id',
                                        'sortOrder' => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'additionalClasses' => 'admin__fieldset-section subscription-profile-currency',
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'sortOrder' => 0,
                            ],
                        ],
                    ],
                ],
                self::GROUP_SUBSCRIPTION_PROFILE_ADD_PRODUCTS => [
                    'children' => [
                        'button_add_product' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => Container::NAME,
                                        'componentType' => Container::NAME,
                                        'component' => 'TNW_Subscriptions/js/components/primary-button',
                                        'template' => 'TNW_Subscriptions/form/element/primary-button',
                                        'displayPrimary' => empty($this->session->getSubQuoteIds()),
                                        'subButtonLeft' => true,
                                        'title' => __('Add Products'),
                                        'actions' => [
                                            [
                                                'targetName' => $modalTarget,
                                                'actionName' => 'toggleModal',
                                            ],
                                            [
                                                'targetName' => $modalTarget . '.grid_container.' . self::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID,
                                                'actionName' => 'render',
                                            ]
                                        ],
                                        'provider' => null,
                                    ],
                                ],
                            ],
                        ],
                        'button_modify_subscriptions' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'formElement' => Container::NAME,
                                        'componentType' => Container::NAME,
                                        'component' => 'TNW_Subscriptions/js/components/primary-button',
                                        'template' => 'TNW_Subscriptions/form/element/primary-button',
                                        'displayPrimary' => false,
                                        'subButtonRight' => true,
                                        'visible' => !empty($this->session->getSubQuoteIds()),
                                        'title' => __('Modify Subscription(s)'),
                                        'provider' => null,
                                    ],
                                ],
                            ],

                        ],
                        'modal' => $this->getModal(),
                        'configurableModal' => $this->getConfigurableModal(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'additionalClasses' => 'admin__fieldset-section subscription-profile-products-container',
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'dataScope' => '',
                                'sortOrder' => 1,
                                'style' => 'max-width: 100%'
                            ],
                        ],
                    ]
                ]
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => Modal::NAME,
                        'options' => [
                            'title' => __('Select a product'),
                            'modalClass' => 'subscriptions-add-product-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                'form_container' => [
                    'children' => [
                        self::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM => $this->getForm()
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'collapsible' => false,
                                'visible' => true,
                                'opened' => true,
                                'additionalClasses' => 'subscriptions-add-product-modal-form-container',
                                'componentType' => Fieldset::NAME,
                                'sortOrder' => 1
                            ],
                        ],
                    ]
                ],
                'grid_container' => [
                    'children' => [
                        self::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID => $this->getGrid(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'collapsible' => false,
                                'visible' => true,
                                'opened' => true,
                                'additionalClasses' => 'subscriptions-add-product-modal-grid-container',
                                'componentType' => Fieldset::NAME,
                                'sortOrder' => 1
                            ],
                        ],
                    ]
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getGrid()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'Magento_Ui/js/form/components/insert-listing',
                        'realTimeLink' => true,
                        'behaviourType' => 'simple',
                        'externalFilterMode' => true,
                        'componentType' => Container::NAME,
                        'autoRender' => false,
                        'dataScope' => Grid::DATA_SCOPE_ADD_PRODUCT_GRID,
                        'externalProvider' => Grid::DATA_SCOPE_ADD_PRODUCT_GRID . '.' . Grid::DATA_SCOPE_ADD_PRODUCT_GRID . '_data_source',
                        'selectionsProvider' => '${ $.ns }.${ $.ns }.tnw_subscriptionprofile_product_columns.ids',
                        'ns' => Grid::DATA_SCOPE_ADD_PRODUCT_GRID,
                        'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'immediateUpdateBySelection' => true,
                        'dataLinks' => ['imports' => false, 'exports' => true],
                        'formProvider' => 'ns = ${ $.namespace }, index = ' . self::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM,
                        'groupCode' => 'products_grid',
                        'groupName' => 'Products grid',
                        'groupSortOrder' => 10,
                        'loading' => false
                    ],
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => false,
                        'label' => '',
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => self::FORM_HANDLE,
                                'buttons' => 1,
                                Form::FORM_DATA_KEY => Form::FORM_DATA_VALUE
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => Form::DATA_SCOPE_MODAL_FORM,
                        'externalProvider' => Form::DATA_SCOPE_MODAL_FORM . '.' . Form::DATA_SCOPE_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                        'formSubmitType' => 'ajax'
                    ],
                ],
            ]
        ];
    }

    /** @return array */
    protected function getConfigurableModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => Modal::NAME,
                        'options' => [
                            'title' => 'Configure product',
                            'modalClass' => 'subscriptions-add-product-configurable-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                self::DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM => $this->getConfigurableForm()
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getConfigurableForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'label' => '',
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => self::CONFIGURABLE_FORM_HANDLE,
                                'buttons' => 1,
                                ConfigurableForm::FORM_DATA_KEY => ConfigurableForm::FORM_DATA_VALUE
                            ]
                        ),
                        'autoRender' => false,
                        'ns' => '' . ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM,
                        'externalProvider' => ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM . '.' . ConfigurableForm::DATA_SCOPE_CONFIGURABLE_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }'
                    ],
                ],
            ]
        ];
    }
}
