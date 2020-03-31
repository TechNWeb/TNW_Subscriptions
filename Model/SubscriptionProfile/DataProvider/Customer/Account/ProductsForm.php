<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Customer\Account;

use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\SummaryProductsForm;

/**
 * Subscription items form data provider for customer account dashboard page.
 */
class ProductsForm extends SummaryProductsForm
{
    /**
     * Form data scope
     */
    const DATA_SCOPE_MODAL_FORM = 'tnw_subscriptionprofile_products_and_services_form';

    /**
     * @var array
     */
    protected $requestFields = [
        'billing_frequency',
        'term',
        'period',
        'start_on',
        'qty',
    ];

    /**
     * @inheritdoc
     */
    protected function getCurrentProfile()
    {
        return $this->profileManager->loadProfileFromRequest('entity_id');
    }

    /**
     * @inheritdoc
     */
    protected function getAdditionalData($objectId, $objectItemId)
    {
        return [
            'entity_id' => $objectId,
            'objectItemId' => $objectItemId,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getPriceDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => 'text',
                        'formElement' => UiForm\Element\Input::NAME,
                        'componentType' => UiForm\Element\Input::NAME,
                        'dataScope' => 'price',
                        'additionalClasses' => 'field-wide right-container',
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
                            'changeValue' => '${ $.parentName}.middle_container.edit_fieldset.billing_frequency:value',
                        ],
                        'priceFormat' => $this->getPriceFormatData(),
                        'modifySubscription' => true,
                        'parentForm' => $this->getCurrentFormName(),
                    ]
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getQtyContainerDefinition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => false,
                        'collapsible' => false,
                        'componentType' => UiForm\Fieldset::NAME,
                        'additionalClasses' => 'qty-fieldset',
                        'template' => 'TNW_Subscriptions/form/element/template/fieldset',
                        'dataScope' => '',
                        'sortOrder' => 110
                    ],
                ],
            ],
            'children' => [
                'qty' => $this->getQtyDefinition()
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
                        'dataScope' => 'qty',
                        'validation' => [
                            'validate-zero-or-greater' => true,
                            'required-entry' => true
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
     * @inheritdoc
     */
    protected function getLeftContainerDefinition()
    {
        $imageHelper = $this->getImageHelper();

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
                                'src' => isset($this->currentProduct) ? $imageHelper->getUrl()
                                    : $imageHelper->getDefaultPlaceholderUrl('small_image')
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getFormEditButtons()
    {
        return [
            'form_button' => $this->getCurrentFormName() . '.edit_button',
        ];
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
                'edit_button' => $this->getEditButton(),
                'description_fieldset' => $this->getDescriptionFieldset()
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
                'name' => $this->getTextFieldDefenition('name'),
                'remove_button' => $this->getRemoveButton(),
                'description' => $this->getTextFieldDefenition('description'),
            ]
        ];

        if (!(bool) $this->currentProduct->getData('tnw_subscr_hide_qty')) {
            $result['children']['qty_container'] = $this->getQtyContainerDefinition();
        }
        $result['children']['edit_fieldset'] = $this->getEditFieldsetDefinition();
        $result['children']['update_button'] = $this->getUpdateButton();
        return $result;
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
                'price' => $this->getPriceDefinition()
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
                        'sortOrder' => 120
                    ],
                ],
            ],
            'children' => [
                'billing_frequency' => $this->getBillingFrequencyDefinition(),
                'term' => $this->getTermDefinition(),
                'period' => $this->getPeriodDefenition(),
                'start_on' => $this->getStartOnDefinition()

            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getPeriodDefenition()
    {
        $infiniteSubscriptions = $this->currentProduct->getData(
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
                        'last_phrase' => __('times'),
                        'validation' => [
                            'validate-greater-than-zero' => true,
                            'required-entry' => true
                        ],
                        'imports' => [
                            'onTermChange' => $this->getCurrentFormName()
                                . '.description_fieldset.middle_container.edit_fieldset.term' . ':value',
                            'showPreview' => '${ $.parentFormName }:previewMode'
                        ],
                        'exports' => [
                            'completePreviewLabel' => $this->getCurrentFormName()
                                . '.description_fieldset.middle_container.edit_fieldset.term' . ':periodPreviewLabel'
                        ],
                        'visibleOnEdit' => !$infiniteSubscriptions,
                        'previewLabelVisible' => false,
                        'previewLabel' => __('Bill %s times'),
                        'previewLabelOnce' => __('Bill once'),
                        'component' => 'TNW_Subscriptions/js/components/field/preview-field-period',
                        'template' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'parentFormName' => $this->currentFormName,
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
        return $this->canEditProfile();
    }

    /**
     * Check if subscription profile can be editable by customer
     *
     * @return bool
     */
    private function canEditProfile()
    {
        $canEdit = false;
        /** @var SubscriptionProfile $currentProfile */
        $currentProfile = $this->getCurrentProfile();
        $status = (int)$currentProfile->getStatus();
        $nonEditableStatuses = [
            ProfileStatus::STATUS_SUSPENDED,
            ProfileStatus::STATUS_CANCELED,
            ProfileStatus::STATUS_COMPLETE,
            ProfileStatus::STATUS_PAST_DUE
        ];
        if ($currentProfile && !in_array($status, $nonEditableStatuses, true)) {
            $canEdit = true;
        }
        return $canEdit;
    }
}
