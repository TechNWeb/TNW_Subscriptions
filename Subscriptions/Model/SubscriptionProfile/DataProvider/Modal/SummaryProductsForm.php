<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use Magento\Ui\Component\Modal;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ProductBillingFrequency\PriceCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\EditSubscriptionProductOptions;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\ModifyForm;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

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
            $billingFrequencyLabel = $this->getBillingFrequencyLabel($subQuote->getBillingFrequencyId());
            /** @var Item $item */
            foreach ($this->getObjectItems($subQuote) as $item) {
                $product = $this->getProductFromItem($item);
                $isProductDeleted = !isset($product);
                $presetQty = (int)$item->getTnwSubscrUnlockPresetQty();
                $itemPrice = $presetQty ? $item->getPrice() * $item->getQty() : $item->getPrice();
                $term = !empty($subQuote->getTerm()) ? 1 : 0;
                $trialStartDate = $subQuote->getTrialStartDate();
                $startOn = isset($trialStartDate) ? $trialStartDate : $subQuote->getStartDate();
                $data[$subQuote->getId()]['item_' . $item->getId()] = [
                    'price' => (string)$itemPrice,
                    'billing_frequency' => $billingFrequencyLabel,
                    'term' => (string)$term,
                    'period' => $subQuote->getTotalBillingCycles(),
                    'unlock_preset_qty' => $presetQty,
                    'start_on' => (new \DateTime($startOn))->format('Y-m-d'),
                    'name' => $isProductDeleted ? $item->getName() : $product->getName(),
                    'description' => $isProductDeleted ? __('Product deleted')
                        : $product->getData('short_description'),
                    'qty' => $item->getQty(),
                    'is_product_deleted' => $isProductDeleted,
                ];
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
                                'additionalClasses' => 'subscription-container',
                                'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                                'dataScope' => '',
                                'sortOrder' => $iterator
                            ]
                        ]
                    ]
                ],
                'editOptionsModal' => $this->getEditOptionsModal(),
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
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
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
                        'modifySubscription' => false,
                        'parentForm' => $this->getCurrentFormName(),
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
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
                        'dataScope' => '',
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
            ]
        ];
    }

    /**
     * @inheritdoc
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
                        'showPreview' => $this->getCurrentFormName() . ':previewMode'
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
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
                            'visible' => '!' . $this->getCurrentFormName() . '.edit_fieldset.term' . ':checked',
                        ],
                        'exports' => [
                            'completePreviewLabel' => $this->getCurrentFormName() . '.edit_fieldset.term' . ':periodPreviewLabel'
                        ],
                        'previewLabelVisible' => false,
                        'previewLabel' => __('Bill %s times'),
                        'previewLabelOnce' => __('Bill once'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field-period',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'showPreview' => $this->getCurrentFormName() . ':previewMode'
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getStartOnDefinition()
    {
        $visibleOnEdit = isset($this->currentProduct)
            ? $this->getStartOnFieldConfig($this->currentProduct->getId())['visible'] : false;
        $nowDate = new \DateTime();

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
                        'visibleOnEdit' => $visibleOnEdit,
                        'showPreview' => $this->getCurrentFormName() . ':previewMode'
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
        return [
            'arguments' => [
                'data' => [
                    'multiple' => false,
                    'config' => [
                        'label' => __('Billing Frequency:'),
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'billing_frequency',
                        'additionalClasses' => 'field-wide',
                        'additionalForGroup' => false,
                        'validation' => ['required-entry' => true],
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'showPreview' => $this->getCurrentFormName() . ':previewMode',
                        'previewLabel' => '%s',
                        'imports' => [
                            'onPriceUpdate' => '${ $.parentName}.price:value'
                        ],
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
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => UiContainer::NAME,
                        'componentType' => UiContainer::NAME,
                        'component' => 'TNW_Subscriptions/js/components/edit-button',
                        'additionalClasses' => 'action-primary action primary sub-button-right',
                        'subButtonRight' => true,
                        'title' => __('Save Changes'),
                        'actions' => [
                            [
                                'targetName' => $this->getCurrentFormName(),
                                'actionName' => 'save',
                            ],
                        ],
                        'provider' => null,
                        'imports' => [
                            'visible' => '!' . $this->getCurrentFormName() . ':buttonPreviewMode'
                        ]
                    ]
                ]
            ]
        ];
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

        if ($currentProfile && count($currentProfile->getProfileProducts()) > 1) {
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

        if ($currentProfile && $currentProfile->canEditProfile()
            && !$this->currentItem->getTnwSubscrUnlockPresetQty()) {
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
    private function getAdditionalConfig()
    {
        return [
            'editOptionsModal' => 'editOptionsModal',
            'editOptionsForm' => EditSubscriptionProductOptions::DATA_SCOPE_EDIT_PRODUCT_OPTIONS_FORM,
            'insertEditOptionsForm' => Product::DATA_SCOPE_EDIT_PRODUCT_MODAL_EDIT_PRODUCT_OPTIONS_FORM,
        ];
    }
}
