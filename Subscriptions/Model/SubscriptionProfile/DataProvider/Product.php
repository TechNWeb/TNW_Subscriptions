<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider;

use TNW\Subscriptions\Model\SubscriptionProfile\ListingDataProvider as MainProvider;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Fieldset;

class Product extends MainProvider
{

    const GROUP_SUBSCRIPTION_PROFILE_PRODUCTS = 'tnw_subscriptionprofile_create_product_listing';

    protected $scopeName = '';

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
     * @return array
     */
    protected function getButtonsMetaData()
    {
        $result = [];

        if ($this->stepPool->getCurrentStep() != StepPool::STEP_PARAM_TYPE_REVIEW){
            $result = [
                static::GROUP_SUBSCRIPTION_PROFILE_PRODUCTS => [
                    'children' => [
                        'button_set' => $this->getButtonSet(
                            __('Add Products'),
                            __('Modify Subscription(s)')
                        ),
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
     * @return array
     */
    protected function getButtonSet(
        Phrase $addProductTitle,
        Phrase $modifySubscriptionTitle
    ) {
        //TODO add links to modal windows

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
}
