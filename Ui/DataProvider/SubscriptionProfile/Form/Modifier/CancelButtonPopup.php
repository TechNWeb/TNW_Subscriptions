<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier;

use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\CancelButtonPopup as DataProvider;

/**
 * Data provider for cancel popup button.
 */
class CancelButtonPopup extends BaseFormModifier
{
    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_merge_recursive(
            $meta,
            [
                'dashboard' => [
                    'children' => [
                        'tnw_subscriptionprofile_cancel_button' => [
                            'children' => [
                                'cancelModal' => $this->getCancelModal(),
                            ],
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label' => '',
                                        'collapsible' => false,
                                        'componentType' => Fieldset::NAME,
                                        'dataScope' => '',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * Get cancel modal window.
     *
     * @return array
     */
    private function getCancelModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'options' => [
                            'title' => __('Are you sure you want to cancel subscription?'),
                            'type' => 'popup',
                            'buttons' => [
                                [
                                    'text' => __('I changed my mind'),
                                    'class' => 'action-secondary',
                                    'actions' => [
                                        'actionCancel'
                                    ]
                                ],
                                [
                                    'text' => __('Confirm'),
                                    'class' => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index = tnw_subscriptionprofile_cancel_button_popup_form',
                                            'actionName' => 'save'
                                        ],
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
            'children' => [
                'tnw_subscriptionprofile_cancel_button_insert_form' => $this->getCancelButtonForm()
            ],
        ];
    }

    /**
     * Get form for cancel button popup.
     *
     * @return array
     */
    private function getCancelButtonForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'update_url' => $this->getUrlBuilder()->getUrl('mui/index/render'),
                        'render_url' => $this->getUrlBuilder()->getUrl('mui/index/render'),
                        'autoRender' => true,
                        'ns' => DataProvider::DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM,
                        'externalProvider' => DataProvider::DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM . '.'
                            . DataProvider::DATA_SCOPE_CANCEL_BUTTON_MODAL_FORM
                            . '_data_source',
                    ],
                ],
            ],
        ];
    }
}
