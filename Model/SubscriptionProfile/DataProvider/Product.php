<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

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
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\Source\ShippingMethods;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Grid;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ConfigurableForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\EditProductOptions;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ModifyForm;
use TNW\Subscriptions\Model\Source\CurrencySelect;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;

class Product extends AbstractDataProvider
{
    /**
     * Render listing url.
     */
    const LISTING_RENDER_URL = 'tnw_subscriptions/subscriptionprofile_create_product/changecurrency';

    /**
     * Listing image id.
     */
    const LISTING_IMAGE_ID = 'product_listing_thumbnail';

    /**#@+
     * Form scope and group values
     */
    const GROUP_SUBSCRIPTION_PROFILE_ADD_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    const DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    const DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS_COLUMNS = 'tnw_subscriptionprofile_product_columns';
    /**#@-*/

    /**#@+
     * Data scopes for child elements
     */
    const DATA_SCOPE_ADD_PRODUCT_MODAL_GRID = 'add_product_modal_grid';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM = 'add_product_modal_form';
    const DATA_SCOPE_ADD_MODIFY_FORM = 'modify_modal_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM = 'add_product_modal_configurable_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_EDIT_PRODUCT_OPTIONS_FORM = 'add_product_modal_edit_product_options_form';
    const DATA_SCOPE_EDIT_PRODUCT_MODAL_EDIT_PRODUCT_OPTIONS_FORM = 'edit_modal_edit_product_options_form';
    /**#@-*/

    /**#@+
     * Layout handles for forms
     */
    const FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_create_add_product';
    const CONFIGURABLE_FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_create_add_product_configurable';
    const MODIFY_HANDLE = 'tnw_subscriptions_subscriptionprofile_create_modify_subscriptions';
    const EDIT_PRODUCT_OPTIONS_FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_create_add_product_edit_options';
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
    protected $urlBuilder;

    /**
     * @var StepPool
     */
    private $stepPool;

    /**
     * @var Context
     */
    private $context;

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
     * @var FormContext
     */
    private $formContext;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
     * @param Context $context
     * @param CurrencySelect $currencySelect
     * @param ShippingMethods $shippingMethods
     * @param DescriptionCreator $frequencyDescriptionCreator
     * @param FormContext $formContext
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
        Context $context,
        CurrencySelect $currencySelect,
        ShippingMethods $shippingMethods,
        DescriptionCreator $frequencyDescriptionCreator,
        FormContext $formContext,
        array $meta = [],
        array $data = [],
        $scopeName = ''
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->context = $context;
        $this->currencySelect = $currencySelect;
        $this->shippingMethods = $shippingMethods;
        $this->frequencyDescriptionCreator = $frequencyDescriptionCreator;
        $this->formContext = $formContext;
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
        $subQuotes = $this->formContext->getSession()->getSubQuotes();
        $counter = 1;
        /** @var ModelQuote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $fullSubscriptionData = null;
            $initialFee = 0;

            if (empty($subQuote->getAllVisibleItems())) {
                continue;
            }

            /** @var Item $item */
            foreach ($subQuote->getAllVisibleItems() as $item) {
                $nonUniqueData = $item->getBuyRequest()->getDataByPath(
                    Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME . DIRECTORY_SEPARATOR . Create::NON_UNIQUE
                );
                if (!$fullSubscriptionData) {
                    $fullSubscriptionData = $item->getBuyRequest()->getDataByPath(
                        Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME
                    );
                    $fullSubscriptionData[Create::NON_UNIQUE]['price'] = 0;
                }
                $qty = !empty($fullSubscriptionData[Create::UNIQUE]['use_preset_qty']) ? 1 : $item->getQty();
                $fullSubscriptionData[Create::NON_UNIQUE]['price'] +=
                    isset($nonUniqueData['price']) ? $nonUniqueData['price'] * $qty: 0;

                $imageHelper = $this->formContext->getImageHelperForQuoteItem($item, $this::LISTING_IMAGE_ID);

                $confOptions = [];
                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                if (isset($options['attributes_info']) && is_array($options['attributes_info'])) {
                    foreach ($options['attributes_info'] as $confOption) {
                        $confOptions[] = ucfirst($confOption['label']) . ': ' . $confOption['value'];
                    }
                }
                $products[] = [
                    'thumbnail_alt' => $imageHelper->getLabel(),
                    'thumbnail_src' => $imageHelper->getUrl(),
                    'qty' => '(x' . $item->getQty() . ')',
                    'name' => $item->getName(),
                    'conf_options' => $confOptions,
                    'id' => $item->getId(),
                ];

                $initialFee += $this->getItemInitialFee($item);
            }
            $subTotal = $subQuote->getGrandTotal();
            $estimatedPayment += (double)$subTotal;

            $fullSubscriptionData[Create::NON_UNIQUE]['totalPrice'] = $subQuote->getSubtotal() + $initialFee;
            $fullSubscriptionData[Create::NON_UNIQUE]['initialFee'] = $initialFee > 0;
            $fullSubscriptionData[Create::NON_UNIQUE]['isVirtual'] = $subQuote->isVirtual();

            $items[] = [
                'title' => __('Subscription') . ' #' . $counter++,
                'products' => $products,
                'frequency_description' => $this->frequencyDescriptionCreator->getDescription($fullSubscriptionData),
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
            $this->formContext->getSession()->getStoreId(),
            $this->formContext->getSession()->getCurrencyId()
        );
    }

    /**
     * @param ModelQuote $quote
     * @return array
     */
    protected function getShippingMethodData($quote)
    {
        $shippingMethods = [];
        $label = '';
        $needShowAttention = false;
        $this->shippingMethods->setQuote($quote);
        if ($this->shippingMethods->canShowShippingMethodLabel()) {
            $label = __('Selected on next step');
            if ($this->stepPool->getCurrentStep() === StepPool::STEP_PARAM_TYPE_PAYMENT) {
                $label = $this->shippingMethods->getCurrentMethodLabel();
                $currentShippingMethod = explode("_", $this->shippingMethods->getCurrentShippingMethod());
                if (!in_array($currentShippingMethod[0], $this->shippingMethods->getDontCostDependedMethodsCodes())) {
                    $needShowAttention = true;
                }
            } elseif ($this->stepPool->getCurrentStep() === StepPool::STEP_PARAM_TYPE_BILLING) {
                $shippingMethods = $this->shippingMethods->getShippingMethodsAsOptionArray();
                $needShowAttention = true;
                $label = '';
            }
        }
        return [
            'label' => $label,
            'methods' => $shippingMethods,
            'needShowAttention' => $needShowAttention,
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
            $this->getProductColumnsData(),
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
     * Returns meta data for subscription listing.
     *
     * @return array
     */
    protected function getMetaData()
    {
        $result = [];

        if ($this->stepPool->getCurrentStep() !== StepPool::STEP_PARAM_TYPE_PAYMENT) {
            $modalTarget = $this->scopeName . '.' . static::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS . '.addProductsModal';
            $modifyModalTarget = $this->scopeName . '.' . static::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS . '.modifyModal';

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
                                        'component' => 'TNW_Subscriptions/js/form/element/currency-select',
                                        'template' => 'TNW_Subscriptions/form/element/currency-select',
                                        'data_form_part' => $this->currencySelect->getCurrentDataFormPartFromStep($this->stepPool->getCurrentStep()),
                                        'dataScope' => '$data.currency_id',
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
                                        'displayPrimary' => empty($this->formContext->getSession()->getSubQuoteIds()),
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
                                        'visible' => !empty($this->formContext->getSession()->getSubQuoteIds()),
                                        'title' => __('Modify Subscription(s)'),
                                        'actions' => [
                                            [
                                                'targetName' => $modifyModalTarget,
                                                'actionName' => 'toggleModal',
                                            ],
                                            [
                                                'targetName' => $modifyModalTarget . '.form_container.' . self::DATA_SCOPE_ADD_MODIFY_FORM,
                                                'actionName' => 'destroyInserted',
                                            ],
                                            [
                                                'targetName' => $modifyModalTarget . '.form_container.' . self::DATA_SCOPE_ADD_MODIFY_FORM,
                                                'actionName' => 'render',
                                            ]
                                        ],
                                        'provider' => null,
                                    ],
                                ],
                            ],

                        ],
                        'addProductsModal' => $this->getModal(),
                        'modifyModal' => $this->getModifyModal(),
                        'configurableModal' => $this->getConfigurableModal(),
                        'editOptionsModal' => $this->getEditOptionsModal(),
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
     * Returns meta data for "Add product" modal window.
     *
     * @return array
     */
    private function getModal()
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
     * Returns meta data for "Modify Subscription(s)" modal window.
     *
     * @return array
     */
    private function getModifyModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => Modal::NAME,
                        'component' => 'TNW_Subscriptions/js/modal/modify-subscriptions-modal',
                        'options' => [
                            'title' => __('Subscription(s)') .' '. $this->stepPool->getCurrentStepTitle(),
                            'modalClass' => 'modify-subscriptions-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                'form_container' => [
                    'children' => [
                        self::DATA_SCOPE_ADD_MODIFY_FORM => $this->getModifyForm()
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'collapsible' => false,
                                'visible' => true,
                                'opened' => true,
                                'additionalClasses' => 'subscriptions-modify-modal-form-container',
                                'componentType' => Fieldset::NAME,
                                'sortOrder' => 1
                            ],
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns meta data for products grid in "Add product" modal window.
     *
     * @return array
     */
    private function getGrid()
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
     * Returns meta data for form in "Add product" modal window.
     *
     * @return array
     */
    private function getForm()
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

    /**
     * Returns meta data for form in "Modify Subscriptions" modal window.
     *
     * @return array
     */
    private function getModifyForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => '',
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => self::MODIFY_HANDLE,
                                ModifyForm::FORM_DATA_KEY => ModifyForm::FORM_DATA_VALUE,
                                'buttons' => 1
                            ]
                        ),
                        'autoRender' => false,
                        'ns' => ModifyForm::DATA_SCOPE_MODAL_FORM,
                        'externalProvider' => ModifyForm::DATA_SCOPE_MODAL_FORM . '.' . ModifyForm::DATA_SCOPE_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                        'formSubmitType' => 'ajax'
                    ],
                ],
            ]
        ];
    }

    /**
     * Returns meta data for configurable modal window.
     *
     * @return array
     */
    private function getConfigurableModal()
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
     * Returns meta data for edit product options modal window.
     *
     * @return array
     */
    private function getEditOptionsModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => Modal::NAME,
                        'options' => [
                            'title' => 'Configure product options',
                            'modalClass' => 'subscriptions-add-product-edit-options-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                self::DATA_SCOPE_ADD_PRODUCT_MODAL_EDIT_PRODUCT_OPTIONS_FORM => $this->getEditProductOptionsForm(),
            ]
        ];
    }

    /**
     * Returns meta data for edit product options form.
     *
     * @return array
     */
    private function getEditProductOptionsForm()
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
                                'handle' => self::EDIT_PRODUCT_OPTIONS_FORM_HANDLE,
                                'buttons' => 1,
                                EditProductOptions::FORM_DATA_KEY => EditProductOptions::FORM_DATA_VALUE,
                            ]
                        ),
                        'autoRender' => false,
                        'ns' => '' . EditProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM,
                        'externalProvider' => EditProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM
                            . '.' . EditProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                    ],
                ],
            ]
        ];
    }

    /**
     * Returns meta data for configurable form.
     *
     * @return array
     */
    private function getConfigurableForm()
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

    /**
     * Get config data
     *
     * @return array
     */
    public function getConfigData()
    {
        $data = parent::getConfigData();
        $data['render_url'] = $this->urlBuilder->getUrl($this::LISTING_RENDER_URL);
        $data['update_url'] = $this->urlBuilder->getUrl($this::LISTING_RENDER_URL);
        return $data;
    }

    /**
     * Returns meta data for product columns.
     *
     * @return array
     */
    protected function getProductColumnsData()
    {
        return [
            self::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS_COLUMNS => [
                'children' => [
                    'shipping_method' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dependsCodes' => $this->shippingMethods->getDontCostDependedMethodsCodes(),
                                    'attentionMessage' => $this->shippingMethods->getShippingAttentionMessage()
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns quote item initial fee.
     *
     * @param Item $item
     * @return float
     */
    private function getItemInitialFee(Item $item)
    {
        $result = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees){
            $result = $initialFees->getSubsInitialFee();
        }

        return $result;
    }
}
