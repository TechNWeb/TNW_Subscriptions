<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form as UiForm;
use Magento\Ui\Component\Modal;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ConfigurableForm;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\EditSubscriptionProductOptions;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ModifyForm;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit\Modifier\EditProduct\Base;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Subscription items form data provider for subscription admin edit page.
 */
class SummaryProductsForm extends ModifyForm
{
    /**
     * Constants for container names.
     */
    const CONTAINER_PREFIX = 'container_';
    const CONTAINER_ITEM_PREFIX = 'container_item_';

    /**
     * Form data scope
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_summary_products_form';

    /**
     * Data scope for child element
     */
    const DATA_SCOPE_EDIT_SUBSCRIPTION_MODAL_EDIT_PRODUCT_OPTIONS_FORM = 'edit_modal_edit_product_options_form';

    /**
     * Data scope for child element
     */
    const DATA_SCOPE_ADD_PRODUCT_MODAL_GRID = 'add_product_modal_grid';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_FORM = 'add_product_modal_form';
    const DATA_SCOPE_ADD_PRODUCT_MODAL_CONFIGURABLE_FORM = 'add_product_modal_configurable_form';

    /**
     * Layout handle for form
     */
    const EDIT_PRODUCT_OPTIONS_FORM_HANDLE = 'tnw_subscriptions_subscriptionprofile_edit_product_edit_options';

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
     * Subscription profile manager.
     *
     * @var Manager
     */
    protected $profileManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param PriceCalculator $priceCalculator
     * @param Context $context
     * @param FormContext $formContext
     * @param PoolInterface $pool
     * @param Manager $profileManager
     * @param Registry $registry
     * @param UrlInterface $urlBuilder
     * @param ProductTypeManagerResolver $productTypeResolver
     * @param StockRegistryInterface $stockRegistry
     * @param string $scope
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PriceCalculator $priceCalculator,
        Context $context,
        FormContext $formContext,
        PoolInterface $pool,
        Manager $profileManager,
        Registry $registry,
        UrlInterface $urlBuilder,
        ProductTypeManagerResolver $productTypeResolver,
        StockRegistryInterface $stockRegistry,
        $scope = '',
        array $meta = [],
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        $this->urlBuilder = $urlBuilder;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $priceCalculator,
            $context,
            $formContext,
            $pool,
            $registry,
            $productTypeResolver,
            $stockRegistry,
            $scope,
            $meta,
            $data
        );
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     */
    public function getData()
    {
        $data = [];
        /** @var SubscriptionProfileInterface $subQuote */
        foreach ($this->getObjects() as $subQuote) {
            $data[$subQuote->getId()]['billing_frequency_id'] = $subQuote->getBillingFrequencyId();
            $data[$subQuote->getId()]['subscription_profile_id'] = $subQuote->getId();
            $data[$subQuote->getId()]['subscription_shipping'] = (float)$subQuote->getData('shipping');
            $data[$subQuote->getId()]['changed_price'] = false;

            /** @var \TNW\Subscriptions\Model\ProductSubscriptionProfile $item */
            foreach ($this->getObjectItems($subQuote) as $item) {
                $product = $this->getProductFromItem($item);
                $isProductDeleted = !isset($product);
                $presetQty = $this->getSubAttributeFromItem(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
                $itemPrice = $item->getPrice();
                $taxAmount = (float) $item->getTaxAmount($subQuote) ?: 0;
                $priceInclTax = $taxAmount ? ($taxAmount + $itemPrice) : null;
                $term = !empty($subQuote->getTerm()) ? 1 : 0;
                $trialStartDate = $subQuote->getTrialStartDate();
                $startOn = isset($trialStartDate) ? $trialStartDate : $subQuote->getOriginalStartDate();
                $data[$subQuote->getId()]['item_' . $item->getId()] = [
                    'price' => $itemPrice,
                    'billing_frequency' => $subQuote->getBillingFrequencyId(),
                    'frequency_data' => $this->getFrequenciesData(false, $product->getId()),
                    'term' => (string)$term,
                    'period' => $subQuote->getTotalBillingCycles(),
                    'trial_period' => $this->getTrialPeriod($product->getId()),
                    'unlock_preset_qty' => $presetQty,
                    'start_on' => (new \DateTime($startOn))->format('Y-m-d'),
                    'name' => $isProductDeleted ? $item->getName() : $product->getName(),
                    'description' => $isProductDeleted ? __('Product deleted')
                        : $product->getData('short_description'),
                    'qty' => (float)$item->getQty(),
                    'is_product_deleted' => $isProductDeleted,
                    'price_incl_tax' => $priceInclTax,
                ];
                $data[$subQuote->getId()]['locked_price'] = $this->priceCalculator->getProductLockPriceSatus($product);

                /** @var Base $modifier */
                foreach ($this->pool->getModifiersInstances() as $modifier) {
                    $modifier->setItem($item);
                    $data[$subQuote->getId()]["item_{$item->getId()}"] =
                        $modifier->modifyData($data[$subQuote->getId()]["item_{$item->getId()}"]);
                }
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function getMetaData()
    {
        $iterator = 0;
        $result = [];
        foreach ($this->getObjects() as $subQuote) {
            $iterator++;
            $result = [
                self::CONTAINER_PREFIX . $subQuote->getId() => [
                    'children' => $this->getChildren($subQuote),
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Products'),
                                'collapsible' => false,
                                'componentType' => UiForm\Fieldset::NAME,
                                'component' => 'TNW_Subscriptions/js/form/element/fieldset-buttons',
                                'additionalClasses' => 'subscription-container',
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset-buttons',
                                'dataScope' => '',
                                'sortOrder' => $iterator,
                            ]
                        ]
                    ]
                ],
                'editOptionsModal' => $this->getEditOptionsModal(),
                'addProductsModal' => $this->addProductModal(),
                'configurableModal' => $this->getConfigurableModal(),
            ];
        }

        return $result;
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
                        'component' => 'TNW_Subscriptions/js/modal/update-product-options-modal',
                        'options' => [
                            'title' => 'Configure product options',
                            'modalClass' => 'subscriptions-add-product-edit-options-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                self::DATA_SCOPE_EDIT_SUBSCRIPTION_MODAL_EDIT_PRODUCT_OPTIONS_FORM => $this->getEditProductOptionsForm(),
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
                                EditSubscriptionProductOptions::FORM_DATA_KEY => EditSubscriptionProductOptions::FORM_DATA_VALUE,
                            ]
                        ),
                        'autoRender' => false,
                        'ns' => '' . EditSubscriptionProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM,
                        'externalProvider' => EditSubscriptionProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM
                            . '.' . EditSubscriptionProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM . '_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                        '__disableTmpl' => [
                            'toolbarContainer' => false
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns meta data for add product modal window.
     *
     * @return array
     */
    private function addProductModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'isTemplate' => false,
                        'componentType' => Modal::NAME,
                        'options' => [
                            'title' => 'Add product',
                            'modalClass' => 'subscriptions-add-product-modal',
                        ]
                    ],
                ],
            ],
            'children' => [
                'form_container' => [
                    'children' => [
                        self::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM => $this->getProductModalForm()
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'collapsible' => false,
                                'visible' => true,
                                'opened' => true,
                                'additionalClasses' => 'subscriptions-add-product-modal-form-container',
                                'componentType' => UiForm\Fieldset::NAME,
                                'sortOrder' => 1
                            ],
                        ],
                    ]
                ],
                'grid_container' => [
                    'children' => [
                        self::DATA_SCOPE_ADD_PRODUCT_MODAL_GRID => $this->getProductModalGrid(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => null,
                                'collapsible' => false,
                                'visible' => true,
                                'opened' => true,
                                'additionalClasses' => 'subscriptions-add-product-modal-grid-container',
                                'componentType' => UiForm\Fieldset::NAME,
                                'sortOrder' => 1
                            ],
                        ],
                    ]
                ],
            ]
        ];
    }

    /**
     * Returns meta data for form in "Add product" modal window.
     *
     * @return array
     */
    private function getProductModalForm()
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
                                'handle' => 'tnw_subscriptions_subscriptionprofile_summary_add_product',
                                'buttons' => 1,
                                Form::FORM_DATA_KEY => Form::FORM_DATA_VALUE
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => 'tnw_subscriptionprofile_summary_add_product_modal_form',
                        'externalProvider' => 'tnw_subscriptionprofile_summary_add_product_modal_form.tnw_subscriptionprofile_summary_add_product_modal_form_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                        'formSubmitType' => 'ajax',
                        'imports' => [
                            'billingFrequencyId' => '${ $.provider }:data.billing_frequency_id',
                            'subscriptionProfileId' => '${ $.provider }:data.subscription_profile_id',
                            '__disableTmpl' => [
                                'billingFrequencyId' => false,
                                'subscriptionProfileId' => false
                            ]
                        ],
                        'exports' => [
                            'billingFrequencyId' => '${ $.externalProvider }:data.billing_frequency',
                            'subscriptionProfileId' => '${ $.externalProvider }:data.subscription_profile_id',
                            '__disableTmpl' => [
                                'billingFrequencyId' => false,
                                'subscriptionProfileId' => false
                            ]
                        ],
                        '__disableTmpl' => [
                            'toolbarContainer' => false
                        ]
                    ],
                ],
            ]
        ];
    }

    /**
     * Returns meta data for products grid in "Add product" modal window.
     *
     * @return array
     */
    private function getProductModalGrid()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'autoRender' => false,
                        'componentType' => 'insertListing',
                        'dataScope' => 'tnw_subscriptionprofile_summary_add_product_modal_listing',
                        'externalProvider' => 'tnw_subscriptionprofile_summary_add_product_modal_listing.tnw_subscriptionprofile_summary_add_product_modal_listing_data_source',
                        'selectionsProvider' => '${ $.ns }.${ $.ns }.tnw_subscriptionprofile_product_columns.ids',
                        'ns' => 'tnw_subscriptionprofile_summary_add_product_modal_listing',
                        'immediateUpdateBySelection' => true,
                        'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'realTimeLink' => true,
                        'dataLinks' => [
                            'imports' => false,
                            'exports' => true
                        ],
                        'behaviourType' => 'simple',
                        'externalFilterMode' => true,
                        'formProvider' => 'ns = ${ $.namespace }, index = ' . self::DATA_SCOPE_ADD_PRODUCT_MODAL_FORM,
                        'groupCode' => 'products_grid',
                        'groupName' => 'Products grid',
                        'groupSortOrder' => 10,
                        'loading' => false,
                        'imports' => [
                            'billingfrequencyId' => '${ $.provider }:data.billing_frequency_id',
                            '__disableTmpl' => [
                                'billingfrequencyId' => false
                            ]
                        ],
                        'exports' => [
                            'billingfrequencyId' => '${ $.externalProvider }:params.billing_frequency_id',
                            '__disableTmpl' => [
                                'billingfrequencyId' => false
                            ]
                        ],
                        '__disableTmpl' => [
                            'selectionsProvider' => false,
                            'formProvider' => false
                        ]
                    ],
                ],
            ],
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
                                'handle' => 'tnw_subscriptions_subscriptionprofile_summary_add_product_configurable',
                                'buttons' => 1,
                                ConfigurableForm::FORM_DATA_KEY => ConfigurableForm::FORM_DATA_VALUE
                            ]
                        ),
                        'autoRender' => false,
                        'ns' => 'tnw_subscriptionprofile_summary_add_product_modal_configurable_form',
                        'externalProvider' => 'tnw_subscriptionprofile_summary_add_product_modal_configurable_form.tnw_subscriptionprofile_summary_add_product_modal_configurable_form_data_source',
                        'toolbarContainer' => '${ $.parentName }',
                        '__disableTmpl' => [
                            'toolbarContainer' => false
                        ]
                    ],
                ],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getStartOnDefinition()
    {
        $visible = isset($this->currentProduct)
            ? $this->getStartOnFieldConfig($this->currentProduct->getId())['visible'] : false;
        $nowDate = new \DateTime();
        $imports = $visible ? [
                'showPreview' => '${ $.parentFormName }:previewMode',
                '__disableTmpl' => [
                    'showPreview' => false
                ]
            ] : [];

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
                        'current_date' => $nowDate->format('m/d/Y'),
                        'validation' => ['required-entry' => true],
                        'component' => 'TNW_Subscriptions/js/components/field/preview-date',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'visible' => $visible,
                        'visibleOnEdit' => $visible,
                        'parentFormName' => $this->currentFormName,
                        'imports' => $imports,
                        'options' => [
                            'minDate' => $nowDate->add(new \DateInterval('P1D'))->format('m/d/Y'),
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getBillingFrequencyDefinition()
    {
        $isTrial = false;

        if (isset($this->currentProduct) && $this->getTrialPeriod($this->currentProduct->getId())) {
            if (!isset($this->profileManager)
                || $this->profileManager->getProfile()->getStatus() == ProfileStatus::STATUS_TRIAL
            ) {
                $isTrial = true;
            }
        }
        return [
            'arguments' => [
                'data' => [
                    'multiple' => false,
                    'config' => [
                        'label' => __('Billing Frequency:'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Select::NAME,
                        'componentType' => UiForm\Field::NAME,
                        'elementTmpl' => 'ui/form/element/select',
                        'caption' => __('-- Please Select --'),
                        'options' => $this->getProductBillingFrequenciesAsOptionArray($this->currentProduct->getId()),
                        'dataScope' => 'billing_frequency',
                        'additionalClasses' => 'field-wide',
                        'additionalForGroup' => false,
                        'validation' => ['required-entry' => true],
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field-frequency',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'previewLabel' => '%s',
                        'imports' => [
                            'showPreview' =>  $isTrial ? false : $this->currentFormName . ':previewMode',
                            'onPriceUpdate' => '${ $.parentName}.price:value',
                            '__disableTmpl' => [
                                'onPriceUpdate' => false
                            ]
                        ],
                        'parentFormName' => $this->currentFormName,
                        'parentForm' => $this->getCurrentFormName(),
                        'priceFormat' => $this->getPriceFormatData(),
                        'currencySymbol' => $this->getCurrentCurrencySymbol(),
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getUpdateButton()
    {
        $result = parent::getUpdateButton();
        $result['arguments']['data']['config']['sortOrder'] = 150;
        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function getCancelButton()
    {
        $result = parent::getCancelButton();
        $result['arguments']['data']['config']['sortOrder'] = 160;
        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getProductFormName()
    {
        return SummaryInsertForm::PRODUCTS_INSERT_FORM;
    }

    /**
     * @inheritdoc
     */
    protected function getObjects()
    {
        return [
            $this->getCurrentProfile()
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getObjectItems(DataObject $object)
    {
        return $object->getVisibleProducts();
    }

    /**
     * Returns product from object item.
     * If magento product delete return null
     *
     * @param DataObject $item
     * @return mixed
     */
    protected function getProductFromItem(DataObject $item)
    {
        try {
            $this->currentProduct = $item->getMagentoProduct();
        } catch (NoSuchEntityException $e) {
            $this->currentProduct = null;
        }

        return $this->currentProduct;
    }

    /**
     * @inheritdoc
     */
    protected function getAdditionalData($objectId, $objectItemId)
    {
        return [
            'subscription_profile_id' => $objectId,
            'objectItemId' => $objectItemId,
        ];
    }

    /**
     * Retrieve current subscription profile.
     *
     * @return null|SubscriptionProfileInterface
     */
    protected function getCurrentProfile()
    {
        return $this->profileManager->loadProfileFromRequest('subscription_profile_id');
    }

    /**
     * Returns 'Remove' button visibility on subscription products list.
     * Depends on products qty in subscription.
     * Qty == 1 => button isn't shown.
     * Qty > 1 => button is shown.
     *
     * @return bool
     */
    protected function getRemoveButtonVisibility()
    {
        $result = false;
        /** @var SubscriptionProfile $currentProfile */
        $currentProfile = $this->getCurrentProfile();

        if ($currentProfile && count($currentProfile->getVisibleProducts()) > 1) {
            $result = true;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function isEditButtonVisible()
    {
        return $this->canEditProfile();
    }

    /**
     * @inheritdoc
     */
    protected function isUpdateButtonVisible()
    {
        return $this->canEditProfile();
    }

    /**
     * Check if subscription profile can be editable
     *
     * @return bool
     */
    private function canEditProfile()
    {
        $canEdit = false;
        /** @var SubscriptionProfile $currentProfile */
        $currentProfile = $this->getCurrentProfile();

        if ($currentProfile && $currentProfile->canEditProfile()) {
            $canEdit = true;
        }

        return $canEdit;
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
            'editOptionsForm' => EditSubscriptionProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM,
            'insertEditOptionsForm' => Product::DATA_SCOPE_EDIT_PRODUCT_MODAL_EDIT_PRODUCT_OPTIONS_FORM,
        ];
    }

    /**
     * Returns item children definition.
     *
     * @param DataObject $subQuote
     * @return array
     */
    protected function getChildren(DataObject $subQuote)
    {
        foreach ($this->getObjectItems($subQuote) as $item) {
            $itemId = $item->getId();
            $objectId = $subQuote->getId();
            $this->currentFormName = $this->getFormFullName($objectId, $itemId);
            $this->currentProduct = $this->getProductFromItem($item);
            $this->currentItem = $item;
            $this->imageHelper = $this->formContext->getImageHelperForSubscriptionProduct($item);
            $itemMeta = [
                'children' => [
                    'form' => $this->getForm($objectId, $itemId),
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
}
