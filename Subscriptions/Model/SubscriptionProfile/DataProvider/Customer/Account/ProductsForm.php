<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Customer\Account;

use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form as UiForm;
use TNW\Subscriptions\Model\Product\Attribute;
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
        $label = __('Price') . ':';
        if (null !== $this->currentProduct
            && $this->getTrialPeriod($this->currentProduct->getId())) {
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
                            'changeValue' => '${ $.parentName}.billing_frequency:value',
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
                        'component' => 'TNW_Subscriptions/js/components/group',
                        'componentType' => UiContainer::NAME,
                        'additionalForGroup' => false,
                        'fieldTemplate' => 'TNW_Subscriptions/form/element/template/field-with-preview',
                        'additionalClasses' => 'qty-container'
                    ]
                ]
            ],
            'children' => [
                'qty' => $this->getQtyDefinition()
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
            'form_button' => $this->getCurrentFormName() . '.edit_fieldset.edit_button',
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

}
