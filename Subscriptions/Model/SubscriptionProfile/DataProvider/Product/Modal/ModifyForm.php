<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote\Item;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\Context as SubscriptionContext;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product as ProductDataProvider;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Class ModifyForm
 */
class ModifyForm extends Form
{
    /**
     * Constants for container names.
     */
    const CONTAINER_PREFIX = 'container_';
    const CONTAINER_ITEM_PREFIX = 'container_item_';

    /**
     * Form data scope
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_create_modify_modal_form';

    /**
     * Form request values
     */
    const FORM_DATA_KEY = 'modify_form_data';
    const FORM_DATA_VALUE = 'new_subscription';

    /**
     * Edit button name.
     */
    const EDIT_BUTTON_NAME = 'edit_button';

    /**
     * Current item form name.
     *
     * @var string
     */
    private $currentFormName;

    /**
     * Product for current item.
     *
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    protected $currentProduct;

    /**
     * Current item.
     *
     * @var DataObject
     */
    protected $currentItem;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var array
     */
    protected $requestFields = [
        'price',
        'billing_frequency',
        'term',
        'period',
        'start_on',
        'qty',
        'super_attribute',
    ];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PriceCalculator $priceCalculator
     * @param SubscriptionContext $context
     * @param Context $formContext
     * @param PoolInterface $pool
     * @param Registry $registry
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
        Registry $registry,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $priceCalculator,
            $context,
            $formContext,
            $pool,
            $scope,
            $meta,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = [];
        foreach ($this->getObjects() as $subQuote) {
            /** @var Item $item */
            foreach ($this->getObjectItems($subQuote) as $item) {
                $product = $this->getProductFromItem($item);
                $subBuyRequest = $item->getBuyRequest()->getDataByPath(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
                $presetQty = (int)$product->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
                $itemPrice = $this->getItemPrice(
                    $presetQty,
                    $subBuyRequest[Create::NON_UNIQUE]['price'],
                    $item
                );
                $itemData = [
                    'price' => $itemPrice,
                    'initial_fee' => $this->getInitialFeeFromItem($item),
                    'billing_frequency' => $subBuyRequest[Create::UNIQUE]['billing_frequency'],
                    'term' => (string)$subBuyRequest[Create::UNIQUE]['term'],
                    'period' => $subBuyRequest[Create::UNIQUE]['period'],
                    'start_on' => $subBuyRequest[Create::UNIQUE]['start_on'],
                    'trial_period' => $this->getTrialPeriod($product->getId()),
                    'name' => $product->getName(),
                    'description' => $product->getData('short_description'),
                    'qty' => $item->getQty(),
                    'product_price' => $product->getPrice(),
                    'unlock_preset_qty' => $presetQty,
                    'frequency_data' => $this->getFrequenciesData(false, $product->getId()),
                    'initial_values' => [
                        'billing_frequency' => $subBuyRequest[Create::UNIQUE]['billing_frequency'],
                        'price' => $itemPrice
                    ]
                ];

                /** @var ModifierInterface $modifier */
                foreach ($this->pool->getModifiersInstances() as $modifier) {
                    $modifier->setItem($item);
                    $itemData = $modifier->modifyData($itemData);
                }

                $data[self::FORM_DATA_VALUE]['item_' . $item->getId()] = $itemData;
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        return array_merge_recursive(
            [],
            $this->getMetaData()
        );
    }

    /**
     * Returns meta data.
     *
     * @return array
     */
    protected function getMetaData()
    {
        $iterator = 0;
        $result = [];
        foreach ($this->getObjects() as $subQuote) {
            $iterator++;
            $result[self::CONTAINER_PREFIX . $subQuote->getId()] = [
                'children' => $this->getChildren($subQuote),
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Subscription Plan') . ' #' . $iterator,
                            'collapsible' => false,
                            'componentType' => UiForm\Fieldset::NAME,
                            'additionalClasses' => 'subscription-container',
                            'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                            'dataScope' => '',
                            'sortOrder' => $iterator
                        ]
                    ]
                ]
            ];
        }

        return $result;
    }

    /**
     * Returns item children definition.
     *
     * @param $subQuote
     * @return array
     */
    protected function getChildren($subQuote)
    {
        foreach ($this->getObjectItems($subQuote) as $item) {
            $itemId = $item->getId();
            $objectId = $subQuote->getId();
            $this->currentFormName = $this->getFormFullName($objectId, $itemId);
            $this->currentProduct = $this->getProductFromItem($item);
            $this->currentItem = $item;
            $itemMeta = [
                'children' => [
                    'form' => $this->getForm($objectId, $itemId)
                ],
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => false,
                            'collapsible' => false,
                            'componentType' => UiForm\Fieldset::NAME,
                            'dataScope' => 'item_' . $itemId,
                            'additionalClasses' => 'subscription-item-form',
                            'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                        ],
                    ],
                ]
            ];

            /** @var ModifierInterface $modifier */
            foreach ($this->pool->getModifiersInstances() as $modifier) {
                $modifier->setItem($this->currentItem);
                $itemMeta = $modifier->modifyMeta($itemMeta);
            }

            $result[self::CONTAINER_ITEM_PREFIX . $itemId] = $itemMeta;
        }

        return !empty($result) ? $result : [];
    }

    /**
     * Return item edit form definition.
     *
     * @param string|int $objectId
     * @param string|int $itemId
     * @return array
     */
    protected function getForm($objectId, $itemId)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiForm::NAME,
                        'componentType' => UiForm::NAME,
                        'component' => 'TNW_Subscriptions/js/components/modify-subscriptions-form',
                        'additionalData' => $this->getAdditionalData($objectId, $itemId),
                        'productsFormName' => $this->getProductFormName(),
                        'requestFields' => $this->getRequestFields(),
                        'editButtons' => $this->getFormEditButtons()
                    ]
                ]
            ],
            'children' => [
                'description_fieldset' => $this->getDescriptionFieldset(),
                'edit_fieldset' => $this->getEditFieldsetDefinition()
            ]
        ];
    }

    /**
     * Retrieve additional data to form.
     *
     * @param string $objectId
     * @param string $objectItemId
     * @return array
     */
    protected function getAdditionalData($objectId, $objectItemId)
    {
        return ['objectId' => $objectId, 'objectItemId' => $objectItemId];
    }

    /**
     * Returns full name of edit form.
     *
     * @param string|int $container
     * @param string|int $containerItem
     * @return string
     */
    protected function getFormFullName($container, $containerItem)
    {
        $formFullName = $this::DATA_SCOPE_MODAL_FORM . '.' . $this::DATA_SCOPE_MODAL_FORM . '.'
            . $this::CONTAINER_PREFIX . $container . '.' . $this::CONTAINER_ITEM_PREFIX . $containerItem . '.form';
        $this->registry->unregister('form_full_name');
        $this->registry->register('form_full_name', $formFullName);

        return $formFullName;
    }

    /**
     * Returns name of product form.
     *
     * @return string
     */
    protected function getProductFormName()
    {
        return ProductDataProvider::DATA_SCOPE_ADD_MODIFY_FORM;
    }

    /**
     * Return description fieldset definition.
     *
     * @return array
     */
    protected function getDescriptionFieldset()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'description-fieldset',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                        'dataScope' => ''
                    ],
                ],
            ],
            'children' => [
                'left_container' => $this->getLeftContainerDefinition(),
                'middle_container' => $this->getMiddleContainerDefinition()
            ]
        ];
    }

    /**
     * Returns edit fieldset definition.
     *
     * @return array
     */
    protected function getEditFieldsetDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'edit-fieldset',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                        'dataScope' => ''
                    ],
                ],
            ],
            'children' => [
                'edit_button' => $this->getEditButton(),
                'billing_frequency' => $this->getBillingFrequencyDefinition(),
                'term' => $this->getTermDefinition(),
                'period' => $this->getPeriodDefenition(),
                'start_on' => $this->getStartOnDefinition(),
                'price' => $this->getPriceDefinition(),
                'trial_period' => $this->getTrialPeriodDefenition(),
                'initial_fee' => $this->getInitialFeeDefinition(),
            ]
        ];
    }

    /**
     * Returns middle container definition from description fieldset.
     *
     * @return array
     */
    protected function getMiddleContainerDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'middle-container',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                    ],
                ],
            ],
            'children' => [
                'name' => $this->getTextFieldDefenition('name'),
                'remove_button' => $this->getRemoveButton(),
                'description' => $this->getTextFieldDefenition('description'),
                'qty_container' => $this->getQtyContainerDefinition(),
                'update_button' => $this->getUpdateButton()
            ]
        ];
    }

    /**
     * Returns qty fields container definition from description fieldset.
     *
     * @return array
     */
    protected function getQtyContainerDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component' => 'TNW_Subscriptions/js/components/group',
                        'componentType' => UiContainer::NAME,
                        'additionalForGroup' => false,
                        'fieldTemplate' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'additionalClasses' => 'qty-container'
                    ]
                ]
            ],
            'children' => [
                'qty' => $this->getQtyDefinition(),
                'qty_edit_button' => $this->getQtyEditButton()
            ]
        ];
    }

    /**
     * Returns left container definition from description fieldset.
     *
     * @return array
     */
    protected function getLeftContainerDefinition()
    {
        $imageHelper = $this->getImageHelper();
        $imageUrl = $this->currentProduct ? $imageHelper->getUrl() : $imageHelper->getDefaultPlaceholderUrl('small_image');
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'left-container',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                    ]
                ]
            ],
            'children' => [
                'image' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => UiForm\Element\Input::NAME,
                                'formElement' => UiForm\Element\Input::NAME,
                                'elementTmpl' => 'TNW_Subscriptions/form/element/image',
                                'additionalClasses' => 'sub-product-image',
                                'src' => $imageUrl
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * Returns left container image from description.
     *
     * @return ImageHelper
     */
    protected function getImageHelper()
    {
        $imageHelper = $this->formContext->getImageHelper();
        if (isset($this->currentProduct)) {
            $imageHelper = $imageHelper->init($this->currentProduct, 'category_page_grid',
                ['type' => 'small_image', 'width' => '240', 'height' => '240']
            );
        }

        return $imageHelper;
    }

    /**
     * Returns simple text field definition.
     *
     * @param $name
     * @return array
     */
    protected function getTextFieldDefenition($name)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'dataType' => 'text',
                        'additionalClasses' => 'field-' . $name,
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                        'dataScope' => $name
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns update button definition.
     *
     * @return array
     */
    protected function getUpdateButton()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-primary action primary sub-button-right',
                        'subButtonRight' => true,
                        'title' => __('Update'),
                        'actions' => [
                            [
                                'targetName' => $this->currentFormName,
                                'actionName' => 'save',
                            ],
                        ],
                        'provider' => null,
                        'imports' => [
                            'visible' => '!' . $this->currentFormName . ':buttonPreviewMode'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns edit button definition.
     *
     * @return array
     */
    protected function getEditButton()
    {
        $additionalClasses = $this->getRemoveButtonVisibility() ? '': 'right';
        $additionalClasses .= ' action-editor';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => $this->isEditButtonVisible(),
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => $additionalClasses,
                        'title' => '',
                        'actions' => [
                            [
                                'targetName' => $this->currentFormName,
                                'actionName' => 'togglePreviewMode',
                            ],
                            [
                                'targetName' => $this->currentFormName,
                                'actionName' => 'toggleButtonPreviewMode',
                            ]
                        ],
                        'provider' => null,
                        'buttonVisibility' => $this->isEditButtonVisible(),
                    ]
                ]
            ]
        ];
    }

    /**
     * Check if edit button is visible
     *
     * @return bool
     */
    protected function isEditButtonVisible()
    {
        return (
            null !== $this->currentProduct
            && !(
                $this->currentProduct->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY)
                && count($this->getProductBillingFrequencies($this->currentProduct->getId())) <= 1
            )
        );
    }

    /**
     * Returns remove button definition.
     *
     * @return array
     */
    protected function getRemoveButton()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'remove-button-icon',
                        'title' => '',
                        'actions' => [
                            [
                                'targetName' => $this->currentFormName,
                                'actionName' => 'removeProduct',
                            ]
                        ],
                        'provider' => null,
                        'imports' => [
                            'isRemoveButtonVisible' => $this->currentFormName . ':previewMode',
                        ],
                        'buttonVisibility' => $this->getRemoveButtonVisibility(),
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns 'Remove' button visibility in subscription product list.
     *
     * @return bool
     */
    protected function getRemoveButtonVisibility()
    {
        return true;
    }

    /**
     * Returns billing frequency field definition.
     *
     * @return array
     */
    protected function getBillingFrequencyDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'multiple' => false,
                    'config' => [
                        'label' => __('Billing Frequency:'),
                        'dataType' => 'boolean',
                        'formElement' => UiForm\Element\RadioSet::NAME,
                        'componentType' => UiForm\Element\RadioSet::NAME,
                        'dataScope' => 'billing_frequency',
                        'additionalClasses' => 'radio-options-one-column sub-legend field-wide',
                        'additionalForGroup' => false,
                        'validation' => ['required-entry' => true],
                        'options' => $this->getProductBillingFrequenciesAsOptionArray($this->currentProduct->getId()),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-checkbox-set',
                        'template' => 'TNW_Subscriptions/form/element/template/checkbox-set-with-preview',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode',
                            'onPriceUpdate' => '${ $.parentName}.price:value'
                        ],
                        'parentForm' => $this->currentFormName,
                        'priceFormat' => $this->getPriceFormatData(),
                        'currencySymbol' => $this->getCurrentCurrencySymbol(),
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns term field definition.
     *
     * @return array
     */
    protected function getTermDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => 'boolean',
                        'formElement' => UiForm\Element\Checkbox::NAME,
                        'componentType' => UiForm\Element\Checkbox::NAME,
                        'valueMap' => ['true' => '1', 'false' => '0'],
                        'default' => '0',
                        'description' => __('Until canceled'),
                        'label' => __('Term:'),
                        'dataScope' => 'term',
                        'required' => true,
                        'additionalClasses' => 'field-wide',
                        'previewLabel' => __('Until canceled'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-checkbox-term',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns period field definition.
     *
     * @return array
     */
    protected function getPeriodDefenition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'additionalClasses' => 'field-wide sub-period-input',
                        'dataType' => 'string',
                        'dataScope' => 'period',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'elementTmpl' => 'TNW_Subscriptions/form/element/period-input',
                        'first_phrase' => __('& bill'),
                        'last_phrase' => __('times'),
                        'validation' => [
                            'validate-greater-than-zero' => true,
                            'required-entry' => true
                        ],
                        'imports' => [
                            'visible' => '!' . $this->currentFormName . '.edit_fieldset.term' . ':checked',
                            'showPreview' => $this->currentFormName . ':previewMode'
                        ],
                        'exports' => [
                            'completePreviewLabel' => $this->currentFormName . '.edit_fieldset.term' . ':periodPreviewLabel'
                        ],
                        'previewLabelVisible' => false,
                        'previewLabel' => __('Bill %s times'),
                        'previewLabelOnce' => __('Bill once'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field-period',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview'
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns start on field definition.
     *
     * @return array
     */
    protected function getStartOnDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Start on:'),
                        'additionalClasses' => 'field-wide field-date',
                        'dataType' => 'string',
                        'dataScope' => 'start_on',
                        'formElement' => UiForm\Element\DataType\Date::NAME,
                        'componentType' => UiForm\Element\DataType\Date::NAME,
                        'current_date' => (new \DateTime())->format('m/d/Y'),
                        'validation' => ['required-entry' => true],
                        'component' => 'TNW_Subscriptions/js/components/field/preview-date',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'visibleOnEdit' => $this->getStartOnFieldConfig($this->currentProduct->getId())['visible'],
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode'
                        ],
                        'options' => [
                            'minDate' => 'new Date()',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns price field definition.
     *
     * @return array
     */
    protected function getPriceDefinition()
    {
        $label = __('Price') . ':';
        if (isset($this->currentProduct) && $this->getTrialPeriod($this->currentProduct->getId())) {
            $label = __('Post trial price:');
        }
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $label,
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'price',
                        'additionalClasses' => 'field-wide',
                        'validation' => [
                            'validate-zero-or-greater' => true,
                            'required-entry' => true
                        ],
                        'addSymbol' => false,
                        'addbefore' => $this->getCurrentCurrencySymbol(),
                        'component' => 'TNW_Subscriptions/js/components/add-product-form-price',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'previewLabel' => $this->getCurrentCurrencySymbol() . '%s',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode',
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
                        ],
                        'priceFormat' => $this->getPriceFormatData(),
                        'modifySubscription' => true,
                        'parentForm' => $this->currentFormName,
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns trial period field definition.
     *
     * @return array
     */
    protected function getTrialPeriodDefenition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Trial Period:'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'trial_period',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                        'additionalClasses' => 'field-wide',
                        'visible' => $this->getTrialPeriod($this->currentProduct->getId()) ? true : false,
                        'previewLabel' => '%s',
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns initial fee field definition.
     *
     * @return array
     */
    protected function getInitialFeeDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Initial Fee'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'initial_fee',
                        'elementTmpl' => 'TNW_Subscriptions/form/element/simple-label',
                        'additionalClasses' => 'field-wide',
                        'visible' => $this->getTrialPeriod($this->currentProduct->getId()) ? true : false,
                        'previewLabel' => '%s',
                        'component' => 'TNW_Subscriptions/js/components/add-product-form-initial-fee',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'imports' => [
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
                        ],
                        'modifySubscription' => true,
                        'parentForm' => $this->currentFormName,
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns qty field definition.
     *
     * @return array
     */
    protected function getQtyDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Qty:'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'additionalClasses' => 'field-qty abs-field-size-x-small',
                        'dataScope' => 'qty',
                        'validation' => [
                            'validate-zero-or-greater' => true,
                            'required-entry' => true
                        ],
                        'component' => 'TNW_Subscriptions/js/components/field/preview-qty',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'previewLabel' => '%s',
                        'imports' => [
                            'canShowEdit' => $this->currentFormName . ':previewMode'
                        ],
                        'exports' => [
                            'value' => '${ $.parentForm}.edit_fieldset.billing_frequency:changeItemPriceLabel',
                        ],
                        'parentForm' => $this->currentFormName,
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns qty edit button definition.
     *
     * @return array
     */
    protected function getQtyEditButton()
    {
        $qtyContainerName = $this->currentFormName . '.description_fieldset.middle_container.qty_container';
        $buttonVisibility = isset($this->currentProduct) ? '' : '!';
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-advanced qty-edit-button action-additional',
                        'additionalForGroup' => true,
                        'displayAsLink' => true,
                        'title' => '[' . __('Modify') . ']',
                        'activeTitle' => '[' . __('Cancel') . ']',
                        'actions' => [
                            [
                                'targetName' => $qtyContainerName . '.qty_edit_button',
                                'actionName' => 'toggle',
                            ],
                            [
                                'targetName' => $this->currentFormName,
                                'actionName' => 'toggleButtonPreviewMode',
                            ]
                        ],
                        'provider' => null,
                        'visible' => $this->isUpdateButtonVisible(),
                        'imports' => [
                            'setUpdateQtyButtonVisibility' => $this->isUpdateButtonVisible() ?
                                $buttonVisibility . $this->currentFormName . ':previewMode' : '',
                        ],
                        'exports' => [
                            'active' => '!' . $qtyContainerName . '.qty:showPreview'
                        ],
                        'parentForm' => $this->currentFormName,
                    ]
                ]
            ]
        ];
    }

    /**
     * Check if update button is visible
     *
     * @return bool
     */
    protected function isUpdateButtonVisible()
    {
        return true;
    }

    /**
     * Returns list of objects to display.
     *
     * @return DataObject[]
     */
    protected function getObjects()
    {
        return $this->formContext->getSession()->getSubQuotes();
    }

    /**
     * Returns list of object items to display.
     *
     * @param DataObject $object
     * @return mixed
     */
    protected function getObjectItems(DataObject $object)
    {
        return $object->getAllVisibleItems();
    }

    /**
     * Returns product from object item.
     *
     * @param DataObject $item
     * @return mixed
     */
    protected function getProductFromItem(DataObject $item)
    {
        return $item->getProduct();
    }

    /**
     * @return array
     */
    public function getRequestFields()
    {
        return $this->requestFields;
    }

    /**
     * @param array $requestFields
     */
    public function setRequestFields(array $requestFields)
    {
        $this->requestFields = $requestFields;
    }

    /**
     * Return currentFormName.
     *
     * @return string
     */
    protected function getCurrentFormName()
    {
        return $this->currentFormName;
    }

    /**
     * Returns initial fee from item.
     *
     * @param DataObject $item
     * @return int
     */
    protected function getInitialFeeFromItem($item)
    {
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
        }

        return !empty($initialFee) ? $initialFee : 0;
    }

    /**
     * Returns item price.
     *
     * @param $presetQty
     * @param string|float $price
     * @param DataObject $item
     * @return float
     */
    protected function getItemPrice($presetQty, $price, $item)
    {
        return $presetQty ? $price * $item->getQty() : $price;
    }

    /**
     * Returns form edit buttons.
     *
     * @return array
     */
    protected function getFormEditButtons()
    {
        $result = [
            'form_button' => $this->currentFormName . '.edit_fieldset.edit_button',
        ];
        if ($this->currentProduct && !$this->currentProduct->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY)) {
            $result['qty_button'] = $this->currentFormName
                . '.description_fieldset.middle_container.qty_container.qty_edit_button';
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        return array_merge(parent::getConfigData(), $this->getAdditionalConfig());
    }

    /**
     * Returns additional list of Ui component names.
     *
     * @return array
     */
    private function getAdditionalConfig()
    {
        return [
            'editOptionsModal' => 'editOptionsModal',
            'editOptionsForm' => EditProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM,
            'insertEditOptionsForm' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_EDIT_PRODUCT_OPTIONS_FORM,
        ];
    }
}
