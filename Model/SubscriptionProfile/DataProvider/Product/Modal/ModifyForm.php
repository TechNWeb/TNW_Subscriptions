<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Model\Context as SubscriptionContext;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Create;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product as ProductDataProvider;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\App\State;

/**
 * Class ModifyForm - form modification modal
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
     * Edit button name name
     */
    const EDIT_BUTTON_NAME = 'edit_button';

    /**
     * Current item form name.
     *
     * @var string
     */
    protected $currentFormName;

    /**
     * Product for current item.
     *
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    protected $currentProduct;

    /**
     * Initialized image helper for product.
     *
     * @var ImageHelper
     */
    protected $imageHelper;

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
        'bundle_option',
        'bundle_option_qty',
        'additional_attribute',
        'use_trial',
    ];

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PriceCalculator $priceCalculator
     * @param SubscriptionContext $context
     * @param Context $formContext
     * @param PoolInterface $pool
     * @param Registry $registry
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param StockRegistryInterface $stockRegistry
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param State $state
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
        ProductTypeManagerResolver $productTypeResolver,
        StockRegistryInterface $stockRegistry,
        SubscriptionProfileRepositoryInterface $profileRepository,
        State $state,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->registry = $registry;
        $this->stockRegistry = $stockRegistry;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $priceCalculator,
            $context,
            $formContext,
            $pool,
            $productTypeResolver,
            $profileRepository,
            $state,
            $scope,
            $meta,
            $data
        );
    }

    /**
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getData()
    {
        $data = [];
        foreach ($this->getObjects() as $subQuote) {
            /** @var Item $item */
            foreach ($this->getObjectItems($subQuote) as $item) {
                $this->currentItem = $item;
                $this->currentProduct = $this->getProductFromItem($item);
                $subBuyRequest = $item->getBuyRequest()->getDataByPath(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
                $presetQty = $this->getSubAttributeFromItem(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
                $itemPrice = $this->getItemPrice(
                    $presetQty,
                    $subBuyRequest[Create::NON_UNIQUE]['current_price'],
                    $item
                );
                $itemData = [
                    'price' => $itemPrice,
                    'initial_fee' => $this->getInitialFeeFromItem($item),
                    'billing_frequency' => $subBuyRequest[Create::UNIQUE]['billing_frequency'],
                    'term' => (string)$subBuyRequest[Create::UNIQUE]['term'],
                    'period' => $subBuyRequest[Create::UNIQUE]['period'],
                    'start_on' => $subBuyRequest[Create::UNIQUE]['start_on'],
                    'trial_period' => $this->getTrialPeriod($this->currentProduct->getId()),
                    'name' => $this->currentProduct->getName(),
                    'description' => $this->currentProduct->getData('short_description'),
                    'qty' => $item->getQty(),
                    'product_price' => $this->currentProduct->getPrice(),
                    'unlock_preset_qty' => $presetQty,
                    'frequency_data' => $this->getFrequenciesData(
                        false,
                        $this->currentProduct->getId(),
                        $this->getAdditionalDataForProduct($item)
                    ),
                    'initial_values' => [
                        'billing_frequency' => $subBuyRequest[Create::UNIQUE]['billing_frequency'],
                        'price' => $itemPrice
                    ],
                    'savings_calculation' => $this->getSavingsCalculationType($this->currentProduct),
                    'use_trial' => (int) $subBuyRequest[Create::UNIQUE]['is_trial'],
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
     * Return additional data from CartItemInterface.
     *
     * @param CartItemInterface $item
     * @return array
     */
    protected function getAdditionalDataForProduct(CartItemInterface $item)
    {
        $product = $this->getProductFromItem($item);

        return $this->productTypeResolver
            ->resolve($product->getTypeId())
            ->getAdditionalData($item);
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
                            'label' => false,
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
     * @param DataObject $subQuote
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getChildren(DataObject $subQuote)
    {
        foreach ($this->getObjectItems($subQuote) as $item) {
            $itemId = $item->getId();
            $objectId = $subQuote->getId();
            $this->currentFormName = $this->getFormFullName($objectId, $itemId);
            $this->currentProduct = $this->getProductFromItem($item);
            $this->currentItem = $item;
            $this->imageHelper = $this->formContext->getImageHelperForQuoteItem($item, 'category_page_grid');
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
                $modifier->setItem($item);
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
                'middle_container' => $this->getMiddleContainerDefinition(),
                'edit_fieldset' => $this->getEditFieldsetDefinition()
            ]
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getEditFieldsetDefinition()
    {
        $result = [
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
                'left' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => UiForm\Fieldset::NAME,
                                'additionalClasses' => 'edit-fieldset__left',
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                'dataScope' => ''
                            ],
                        ],
                    ],
                    'children' => [
                        'billing_frequency' => $this->getBillingFrequencyDefinition(),
                        'price' => $this->getPriceDefinition(),
                        'update_button' => $this->getUpdateButton(),
                        'cancel_button' => $this->getCancelButton()
                    ]
                ],
                'right' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => false,
                                'collapsible' => false,
                                'componentType' => UiForm\Fieldset::NAME,
                                'additionalClasses' => 'edit-fieldset__right',
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                'dataScope' => ''
                            ],
                        ],
                    ],
                    'children' => [
                        'term' => $this->getTermDefinition(),
                        'period' => $this->getPeriodDefenition(),
                        'start_on' => $this->getStartOnDefinition(),
                        'trial_period' => $this->getTrialPeriodDefenition(),
                        'initial_fee' => $this->getInitialFeeDefinition(),
                    ]
                ],
            ]
        ];

        $hideQty = $this->getSubAttributeFromItem(Attribute::SUBSCRIPTION_HIDE_QTY);
        if (!$hideQty) {
            $result['children']['left']['children']['qty_container'] = $this->getQtyDefinition();
        }
        $canSkipTrial = $this->getSubAttributeFromItem(Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP);
        if ($canSkipTrial && $this->isTrialAllowed($this->currentProduct->getId())) {
            $result['children']['left']['children']['use_trial'] = $this->getUseTrialDefenition();
        }
        return $result;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getMiddleContainerDefinition()
    {
        $hideQty = $this->getSubAttributeFromItem(Attribute::SUBSCRIPTION_HIDE_QTY);
        $qty = false;
        if (!$hideQty) {
            $qty = (float)$this->currentItem->getQty() . ' x ';
        }
        $result =  [
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
                'name' => $this->getTextFieldDefenition('name', $qty),
                'remove_button' => $this->getRemoveButton(),
                'edit_button' => $this->getEditButton(),
                'description' => $this->getTextFieldDefenition('description'),
            ]
        ];

        return $result;
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
        $imageUrl = $this->currentProduct
            ? $imageHelper->getUrl()
            : $imageHelper->getDefaultPlaceholderUrl('small_image');
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
        return $this->imageHelper;
    }

    /**
     * Returns simple text field definition.
     *
     * @param $name
     * @param bool $label
     * @return array
     */
    protected function getTextFieldDefenition($name, $label = false)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $label,
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
                        'sortOrder' => 40,
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-primary action primary sub-button-left',
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
     * Returns cancel button definition.
     *
     * @return array
     */
    protected function getCancelButton()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'sortOrder' => 50,
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-primary action secondary sub-button-left',
                        'title' => __('Cancel'),
                        'actions' => [
                            [
                                'targetName' => $this->currentFormName,
                                'actionName' => 'togglePreviewMode',
                            ],
                            [
                                'targetName' => $this->currentFormName,
                                'actionName' => 'toggleButtonPreviewMode',
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
        $additionalClasses = $this->getRemoveButtonVisibility() ? '' : 'right';
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
        return null !== $this->currentProduct
            && count($this->getProductBillingFrequencies($this->currentProduct->getId()));
    }

    /**
     * Returns remove button definition.
     *
     * @return array
     */
    protected function getRemoveButton()
    {
        $isDisable = false;
        $data = $this->getData();
        if (is_array($data)
            && array_key_exists('new_subscription', $data)
            && count($data['new_subscription']) <= 1
        ) {
            $isDisable = true;
        }

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
                        'disabled' => $isDisable,
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
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getBillingFrequencyDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'multiple' => false,
                    'config' => [
                        'sortOrder' => 10,
                        'label' => __('Billing Frequency:'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Select::NAME,
                        'componentType' => UiForm\Field::NAME,
                        'elementTmpl' => 'ui/form/element/select',
                        'caption' => __('-- Please Select --'),
                        'dataScope' => 'billing_frequency',
                        'additionalClasses' => 'radio-options-one-column sub-legend field-wide',
                        'additionalForGroup' => false,
                        'validation' => ['required-entry' => true],
                        'options' => $this->getProductBillingFrequenciesAsOptionArray($this->currentProduct->getId()),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field-frequency',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode',
                            'onQtyUpdate' => $this->currentFormName . ':previewMode',
                            'onPriceUpdate' => '${ $.parentName}.price:value',
                            '__disableTmpl' => [
                                'onPriceUpdate' => false
                            ]
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
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getTermDefinition()
    {
        $infiniteSubscriptions = (bool)$this->getSubAttributeFromItem(
            Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS
        );
        if (isset($this->profileManager)) {
            $defaultTerm = $this->profileManager->getProfile()->getTerm();
        } elseif (isset($this->currentItem)) {
            $subBuyRequest = $this->currentItem->getBuyRequest()
                ->getDataByPath(Create::SUBSCRIPTION_BUY_REQUEST_PARAM_NAME);
            $defaultTerm = (string)$subBuyRequest[Create::UNIQUE]['term'] ?? '1';
        } else {
            $defaultTerm = '1';
        }
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'multiple' => false,
                        'dataType' => 'boolean',
                        'formElement' => UiForm\Element\RadioSet::NAME,
                        'componentType' => UiForm\Element\RadioSet::NAME,
                        'options' => [
                            ['value' => 1, 'label' => __('until canceled')],
                            ['value' => 0, 'label' => __('stop after')],
                        ],
                        'default' => $defaultTerm,
                        'disabled' => $infiniteSubscriptions,
                        'description' => __('until canceled'),
                        'label' => __('Term:'),
                        'dataScope' => 'term',
                        'required' => true,
                        'additionalClasses' => 'field-wide field-term',
                        'previewLabel' => __('until canceled'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-checkbox-term',
                        'template' => 'TNW_Subscriptions/form/element/template/checkbox-set-with-preview',
                        'tooltip' => [
                            'description' => __('Remaining orders under agreed subscription term. ' .
                            'After all orders from this term are created,' .
                            'the subscription profile will be marked Complete.')
                        ],
                        'imports' => [
                            'showPreview' => '${ $.parentFormName }:previewMode',
                            '__disableTmpl' => [
                                'showPreview' => false
                            ]
                        ],
                        'parentFormName' => $this->currentFormName,
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPeriodDefenition()
    {
        $objects = $this->getObjects();
        $firstObject = array_shift($objects);
        $term = $firstObject ? $firstObject->getTotalBillingCycles() : false;
        $defaultPeriodValue = $term == 1 ? $term : self::DEFAULT_PERIOD_VALUE;
        $infiniteSubscriptions = (bool)$this->getSubAttributeFromItem(
            Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS
        );
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
                        'first_phrase' => '',
                        'last_phrase' => __('payments'),
                        'validation' => [
                            'required-entry' => true,
                            'validate-number-range' => (int) $defaultPeriodValue . '-9999999999',
                        ],
                        'imports' => [
                            'onTermChange' => $this->getCurrentFormName()
                                . '.description_fieldset.edit_fieldset.right.term' . ':value',
                            'showPreview' => '${ $.parentFormName }:previewMode',
                            '__disableTmpl' => [
                                'showPreview' => false
                            ]
                        ],
                        'exports' => [
                            'completePreviewLabel' => $this->getCurrentFormName()
                                . '.description_fieldset.edit_fieldset.right.term' . ':periodPreviewLabel'
                        ],
                        'visibleOnEdit' => !$infiniteSubscriptions,
                        'previewLabelVisible' => false,
                        'previewLabel' => __('Committed to %s orders'),
                        'previewLabelOnce' => __('Committed to 1 order'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field-period',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'parentFormName' => $this->currentFormName,
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPriceDefinition()
    {
        $label = __('Price') . ':';
        $isTrial = false;

        if (isset($this->currentProduct) && $this->getTrialPeriod($this->currentProduct->getId())) {
            $label = __('Post trial price:');
            if (!isset($this->profileManager)
            || $this->profileManager->getProfile()->getStatus() == ProfileStatus::STATUS_TRIAL
            ) {
                $isTrial = true;
            }
        }
        $disabled = false;
        if (isset($this->currentProduct)) {
            $disabled = $this->currentProduct->getData('tnw_subscr_lock_product_price') ? true : false;
        }
        $unlockQty = (bool)$this->getSubAttributeFromItem(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
        $notice = $unlockQty
            ? __('The price is fo all items, excluding tax (if any)')
            : __('The price is per item, excluding tax (if any)');
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'sortOrder' => 20,
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
                        'notice' => $notice,
                        'disabled' => $disabled,
                        'component' => 'TNW_Subscriptions/js/components/add-product-form-price',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'previewLabel' => $this->getCurrentCurrencySymbol() . '%s',
                        'imports' => [
                            'showPreview' => $isTrial ? false : $this->currentFormName . ':previewMode',
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
                            'priceInclTax' => '${ $.provider }',
                            '__disableTmpl' => [
                                'changeValue' => false,
                                'priceInclTax' => false
                            ]
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
                        'visible' => $this->getTrialPeriod(
                            $this->currentProduct
                                ? $this->currentProduct->getId()
                                : null
                        ) ? true : false,
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
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
                        'visible' => (bool)$this->getInitialFeeFromItem($this->currentItem),
                        'previewLabel' => $this->getCurrentCurrencySymbol() . '%s',
                        'component' => 'TNW_Subscriptions/js/components/add-product-form-initial-fee',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'imports' => [
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
                            '__disableTmpl' => [
                                'changeValue' => false
                            ]
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
        $canUseDecimals = $this->canUseQtyDecimals();
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'sortOrder' => 30,
                        'label' => __('Qty:'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'additionalClasses' => 'field-qty field-wide',
                        'dataScope' => 'qty',
                        'validation' => [
                            'validate-greater-than-zero' => true,
                            'required-entry' => true,
                            'validate-digits' => !$canUseDecimals
                        ],
                        'component' => 'TNW_Subscriptions/js/components/field/preview-qty',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'previewLabel' => '%s',
                        'parentFormName' => $this->currentFormName,
                    ]
                ]
            ]
        ];
    }

    /**
     * @return mixed
     */
    protected function canUseQtyDecimals()
    {
        if ($this->currentProduct) {
            return $this->stockRegistry->getStockItem(
                $this->currentProduct->getId(),
                $this->currentProduct->getStore()->getWebsiteId()
            )
                ->getIsQtyDecimal();
        }
        return false;
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
                        'validation' => [
                            'validate-greater-than-zero' => true,
                            'required-entry' => true
                        ],
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
            $initialFee = number_format($initialFees->getSubsInitialFee(), 2, '.', '');
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
        return [
            'form_button' => $this->currentFormName . '.description_fieldset.middle_container.edit_button',
        ];
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
    public function getAdditionalConfig()
    {
        return [
            'editOptionsModal' => 'editOptionsModal',
            'editOptionsForm' => EditProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM,
            'insertEditOptionsForm' => Product::DATA_SCOPE_ADD_PRODUCT_MODAL_EDIT_PRODUCT_OPTIONS_FORM,
        ];
    }

    /**
     * Get subscription attribute from current item. Respect inheritance.
     * @param string $subAttribute
     * @return array|mixed|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSubAttributeFromItem($subAttribute)
    {
        $arguments = [];
        $childProduct = $this->getChildProductFromCurrentItem() ?? null;
        if ($childProduct) {
            $arguments['child_product'] = $childProduct;
        }
        if ($this->currentProduct) {
            $productData = $this->getProductObjectData($this->currentProduct->getId(), $arguments);
            return $productData->getData($subAttribute);
        }
        return null;
    }

    /**
     * Returns Use Trial field definition.
     *
     * @return array
     */
    protected function getUseTrialDefenition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'sortOrder' => 35,
                        'multiple' => false,
                        'label' => __('Use Trial:'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Checkbox::NAME,
                        'componentType' => UiForm\Element\Checkbox::NAME,
                        'dataScope' => 'use_trial',
                        'elementTmpl' => 'ui/form/components/single/switcher',
                        'additionalClasses' => 'field-wide',
                        'visible' => true,
                        'previewLabelYes' => __('Yes'),
                        'previewLabelNo' => __('No'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-checkbox-trial',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'imports' => [
                            'showPreview' => $this->currentFormName . ':previewMode'
                        ],
                        'modifySubscription' => true,
                        'parentForm' => $this->currentFormName,
                        'valueMap' => [
                            'false' => 0,
                            'true' => 1
                        ],
                        'toggleLabels' => [
                            'on' => __('Yes'),
                            'off' => __('No')
                        ]
                    ]
                ]
            ]
        ];
    }
}
