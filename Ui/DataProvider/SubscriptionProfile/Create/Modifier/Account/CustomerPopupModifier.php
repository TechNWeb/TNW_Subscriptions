<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Modifier\Account;

use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal\CustomerExistsForm;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Class to modify customer popup.
 */
class CustomerPopupModifier implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    const CUSTOMER_EXISTS_FORM_HANDLER = 'tnw_subscriptions_subscriptionprofile_customer_exists';

    /**
     * Url Builder.
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CustomerPopupModifier constructor.
     * @param UrlInterface $urlBuilder
     * @param QuoteSessionInterface $session
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        QuoteSessionInterface $session,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $groupId = $this->session->getCustomerGroup();
        if (!$groupId) {
            $groupId = $this->scopeConfig->getValue(
                'customer/create_account/default_group',
                'store',
                $this->session->getStoreId()
            );
        }
        $meta = array_merge_recursive(
            $meta,
            [
                'customer_account_already_exists' => [
                    'children' => [
                        'customerModal' => $this->getCustomerExistModal(),
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
                'account_info' => [
                    'children' => [
                        'account' => [
                            'children' => [
                                'group' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'default' => $groupId
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        return $meta;
    }

    /**
     * Get customer exists modal window.
     *
     * @return array
     */
    private function getCustomerExistModal()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'component' => 'TNW_Subscriptions/js/modal/modal-component-customer-exists-popup',
                        'options' => [
                            'modalClass' => 'modal-popup customer_exists_popup',
                        ],
                        'onCancel' => 'closePopup',
                    ],
                ],
            ],
            'children' => [
                'customer_account_already_exists' => $this->getCustomerExistForm()
            ],
        ];
    }

    /**
     * Get form fo customer exists popup.
     *
     * @return array
     */
    private function getCustomerExistForm()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'visible' => true,
                        'componentType' => Container::NAME,
                        'component' => 'TNW_Subscriptions/js/components/insert-form',
                        'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'render_url' => $this->urlBuilder->getUrl(
                            'mui/index/render_handle',
                            [
                                'handle' => self::CUSTOMER_EXISTS_FORM_HANDLER
                            ]
                        ),
                        'autoRender' => true,
                        'ns' => CustomerExistsForm::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM,
                        'externalProvider' => CustomerExistsForm::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM
                            . '.' . CustomerExistsForm::DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM
                            . '_data_source',
                    ],
                ],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
