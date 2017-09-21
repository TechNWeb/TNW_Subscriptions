<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface as BillingFrequencyRepository;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface as RecurringOptionRepository;
use TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;

/**
 * Class ModifyForm
 */
class ModifyForm extends Form
{
    /**#@+
     * Constants for container names.
     */
    const CONTAINER_PREFIX = 'container_';
    const CONTAINER_ITEM_PREFIX = 'container_item_';
    /**#@-*/

    /**#@+
     * Form data scope
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_create_modify_modal_form';
    /**#@-*/

    /**#@+
     *
     * Form request values
     */
    const FORM_DATA_KEY = 'modify_form_data';
    const FORM_DATA_VALUE = 'new_subscription';
    /**#@-*/

    const EDIT_BUTTON_NAME = 'edit_button';

    /**
     * Image helper.
     *
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * Current item form name.
     *
     * @var string
     */
    private $currentFormName;

    /**
     * Product for current item.
     *
     * @var
     */
    private $currentProduct;

    /**
     * ModifyForm constructor.
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
     * @param ImageHelper $imageHelper
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
        ImageHelper $imageHelper,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->imageHelper = $imageHelper;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $productRepository, $repository,
            $frequencyRepository, $request, $unitType, $priceCalculator, $storeManager, $config, $sessionQuote,
            $currencyFactory, $context, $scope, $meta, $data);
    }


    /**
     * {@inheritdoc}
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
                $itemPrice = $presetQty
                    ? $subBuyRequest[Create::NON_UNIQUE]['price'] * $item->getQty()
                    : $subBuyRequest[Create::NON_UNIQUE]['price'];
                $data[self::FORM_DATA_VALUE]['item_' . $item->getId()] = [
                    'price' => $itemPrice,
                    'initial_fee' => $subBuyRequest[Create::NON_UNIQUE]['initial_fee'],
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
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $meta = array_merge_recursive(
            [],
            $this->getMetaData()
        );

        return $meta;
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
        $result = [];
        $orderIterator = 0;
        foreach ($this->getObjectItems($subQuote) as $item) {
            $itemId = $item->getId();
            $objectId = $subQuote->getId();
            $this->currentFormName = $this->getFormFullName($objectId, $itemId);
            $this->currentProduct = $this->getProductFromItem($item);
            $orderIterator++;
            $result[self::CONTAINER_ITEM_PREFIX . $itemId] = [
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
                            'sortOrder' => $orderIterator
                        ],
                    ],
                ]
            ];
        }

        return $result;
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
                        'objectId' => $objectId,
                        'objectItemId' => $itemId,
                        'editButtons' => [
                            'form_button' => $this->currentFormName . '.edit_fieldset.edit_button',
                            'description_button' => $this->currentFormName . '.description_fieldset.left_container.edit_button',
                            'qty_button' => $this->currentFormName . '.description_fieldset.middle_container.qty_container.qty_edit_button'
                        ]
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
     * Returns full name of edit form.
     *
     * @param string|int $container
     * @param string|int $containerItem
     * @return string
     */
    protected function getFormFullName($container, $containerItem)
    {
        return self::DATA_SCOPE_MODAL_FORM . '.' . self::DATA_SCOPE_MODAL_FORM
            . '.' . self::CONTAINER_PREFIX . $container
            . '.' . self::CONTAINER_ITEM_PREFIX . $containerItem
            . '.form';
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
                    ],
                ],
            ],
            'children' => [
                'name' => $this->getTextFieldDefenition('name'),
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
                        'component' => 'Magento_Ui/js/form/components/group',
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
        $imageHelper = $this->imageHelper->init(
            $this->currentProduct,
            'category_page_grid',
            [
                'type' => 'small_image',
                'width' => '240',
                'height' => '240',
            ]
        );

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'left-container'
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
                                'src' => $imageHelper->getUrl()
                            ]
                        ]
                    ]
                ],
                'edit_button' => $this->getEditButton(),
                'remove_button' => $this->getRemoveButton(),

            ]
        ];
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
                        'additionalClasses' => 'admin__field-wide field-' . $name,
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
                        'additionalClasses' => 'action-primary sub-button-right',
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
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-editor',
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
                        'provider' => null
                    ]
                ]
            ]
        ];
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
                            'visible' => $this->currentFormName . ':previewMode'
                        ]
                    ]
                ]
            ]
        ];
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
                        'additionalClasses' => 'radio-options-one-column sub-legend admin__field-wide',
                        'additionalForGroup' => false,
                        'validation' => ['required-entry' => true],
                        'options' => $this->getProductBillingFrequenciesAsOptionArray(
                            $this->currentProduct->getId()
                        ),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-checkbox-set',
                        'template' => 'TNW_Subscriptions/form/element/template/checkbox-set-with-preview',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode',
                            'onPriceUpdate'=> '${ $.parentName}.price:value'
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
                        'additionalClasses' => 'admin__field-wide',
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
                        'additionalClasses' => 'admin__field-wide sub-period-input',
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
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field',
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
        $visibleOnEdit = $this->getStartOnFieldConfig($this->currentProduct->getId())['visible'];
        $nowDate = new \DateTime();

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Start on:'),
                        'additionalClasses' => 'admin__field-wide admin__field-date',
                        'dataType' => 'string',
                        'dataScope' => 'start_on',
                        'formElement' => UiForm\Element\DataType\Date::NAME,
                        'componentType' => UiForm\Element\DataType\Date::NAME,
                        'current_date' => $nowDate->format('m/d/Y'),
                        'validation' => ['required-entry' => true],
                        'component' => 'TNW_Subscriptions/js/components/field/preview-date',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'visibleOnEdit' => $visibleOnEdit,
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns price field definition.
     *
     * @return array
     */
    protected function getPriceDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $this->getTrialPeriod($this->currentProduct->getId()) ?
                            __('Post trial price:') : __('Price') . ':',
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'price',
                        'additionalClasses' => 'admin__field-wide',
                        'validation' => [
                            'validate-zero-or-greater' => true,
                            'required-entry' => true
                        ],
                        'addSymbol' => false,
                        'addbefore' => $this->getCurrentCurrencySymbol(),
                        'component' => 'TNW_Subscriptions/js/components/add-product-form-price',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'previewLabel' =>  $this->getCurrentCurrencySymbol() . '%s',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode',
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
                        ],
                        'priceFormat' => $this->getPriceFormatData(),
                        'modifySubscription' => true,
                        'parentForm' => $this->currentFormName,                    ]
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
                        'additionalClasses' => 'admin__field-wide',
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
                        'additionalClasses' => 'admin__field-wide',
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

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-advanced qty-edit-button',
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
                        'imports' => [
                            'setUpdateQtyButtonVisibility' => $this->currentFormName . ':previewMode'
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
     * Returns list of objects to display.
     *
     * @return DataObject[]
     */
    protected function getObjects()
    {
        return $this->sessionQuote->getSubQuotes();
    }

    /**
     * Returns list of object items to display.
     *
     * @param DataObject $object
     * @return mixed
     */
    protected function getObjectItems(DataObject $object)
    {
        return $object->getAllItems();
    }

    /**
     * Returns product from object item.
     *
     * @param DataObject $item
     * @return mixed
     */
    protected function getProductFromItem(DataObject $item)
    {
        return $this->productRepository->getById($item->getProduct()->getId());
    }
}