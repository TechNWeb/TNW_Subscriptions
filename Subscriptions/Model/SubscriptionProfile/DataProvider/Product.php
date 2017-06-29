<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Container;
use Magento\Ui\DataProvider\AbstractDataProvider;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ConfigurableForm;
use \Magento\Framework\Api\Filter;


class Product extends AbstractDataProvider
{
    const GROUP_SUBSCRIPTION_PROFILE_ADD_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    const DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS = 'tnw_subscriptionprofile_create_add_products';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_GRID = 'add_product_modal_grid';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM = 'add_product_modal_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM = 'add_product_modal_configurable_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM_BUTTON = 'add_to_subscription_button';
    const DEFAULT_SCOPE_NAME = 'tnw_subscriptionprofile_create_product_listing';

    protected $scopeName = '';
    /** @var [] */
    protected $loadedData;
    /** @var UrlInterface */
    protected $urlBuilder;
    /** @var StepPool */
    protected $stepPool;

    /**
     * Product constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param UrlInterface $urlBuilder
     * @param StepPool $stepPool
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
        array $meta = [],
        array $data = [],
        $scopeName = ''
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->stepPool = $stepPool;
        $this->scopeName = $scopeName ? $scopeName : self::DEFAULT_SCOPE_NAME . '.' . self::DEFAULT_SCOPE_NAME;
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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = parent::getMeta();

        $meta = array_merge_recursive(
            $meta,
            $this->getButtonsMetaData()
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
    protected function getButtonsMetaData()
    {
        $result = [];

        if ($this->stepPool->getCurrentStep() != StepPool::STEP_PARAM_TYPE_REVIEW) {
            $result = [
                self::GROUP_SUBSCRIPTION_PROFILE_ADD_PRODUCTS => [
                    'children' => [
                        'button_set' => $this->getButtonSet(
                            __('Add Products'),
                            __('Modify Subscription(s)'),
                            static::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS

                        ),
                        'modal' => $this->createAddProductModal(),
                        'configurableModal' => $this->createConfigurableModal(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'additionalClasses' => 'admin__fieldset-section subscription-prodile-products-container',
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => Fieldset::NAME,
                                'dataScope' => '',
                                'sortOrder' => 0,
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
     * @param Phrase $addProductTitle
     * @param Phrase $modifySubscriptionTitle
     * @param $scope
     * @return array
     */
    protected function getButtonSet(
        Phrase $addProductTitle,
        Phrase $modifySubscriptionTitle,
        $scope
    ) {
        //TODO add links to modal windows
        $modalTarget = $this->scopeName . '.' . $scope . '.modal';
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'label' => false,
                        'content' => '',
                        'template' => 'ui/form/components/complex',
                    ],
                ],
            ],
            'children' => [
                'button_add_product' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'title' => $addProductTitle,
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
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'title' => $modifySubscriptionTitle,
                                'provider' => null,
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function createAddProductModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => 'modal',
                        'imports' => [
                            'state' => '!index=tnw_subscriptionprofile_create_add_product_modal_form:responseStatus'
                        ],
                        'options' => [
                            'title' => 'Select a product',
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
                        'dataScope' => 'tnw_subscriptionprofile_create_add_product_modal_listing',
                        'externalProvider' => 'tnw_subscriptionprofile_create_add_product_modal_listing.tnw_subscriptionprofile_create_add_product_modal_listing_data_source',
                        'selectionsProvider' => '${ $.ns }.${ $.ns }.tnw_subscriptionprofile_product_columns.ids',
                        'ns' => 'tnw_subscriptionprofile_create_add_product_modal_listing',
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
                        'componentType' => 'container',
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => 'tnw_subscriptions_subscriptionprofile_create_add_product',
                                'buttons' => 1,
                                Form::FORM_DATA_KEY => Form::FORM_DATA_VALUE
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => 'tnw_subscriptionprofile_create_add_product_modal_form',
                        'externalProvider' => Form::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                        'formSubmitType' => 'ajax'
                    ],
                ],
            ]
        ];
    }


    /** @return array */
    protected function createConfigurableModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => 'modal',
                        'imports' => [
                            'state' => '!index=add_product_modal_configurable_form:responseStatus'
                        ],
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

    protected function getConfigurableForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'label' => '',
                        'componentType' => 'container',
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'dataScope' => '',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => 'tnw_subscriptions_subscriptionprofile_create_add_product_configurable',
                                'buttons' => 1
                            ]
                        ),
                        'autoRender' => false,
                        'ns' => 'tnw_subscriptionprofile_create_add_product_modal_configurable_form',
                        'externalProvider' => ConfigurableForm::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }'
                    ],
                ],
            ]
        ];
    }
}
