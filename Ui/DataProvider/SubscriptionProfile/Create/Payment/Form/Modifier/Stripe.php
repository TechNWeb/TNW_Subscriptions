<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Payment\Model\Config;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use TNW\Stripe\Model\Ui\ConfigProvider as StripeConfigProvider;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use Magento\Framework\View\LayoutFactory;

/**
 * Stripe payment methods form modifier.
 */
class Stripe extends Base
{
    const SORT_ORDER = 25;

    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @var \Magento\Braintree\Gateway\Config\Config
     */
    private $stripeConfig;

    /**
     * @var StripeAdapterFactory
     */
    private $stripeAdapterFactory;

    /**
     * @var string
     */
    private $clientToken = '';
    /**
     * @var LayoutFactory
     */
    private $layoutFactory;
    /**
     * @param \TNW\Subscriptions\Model\Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Braintree\Gateway\Config\Config $stripeConfig
     * @param Config $paymentConfig
     * @param StripeAdapterFactory $stripeAdapterFactory
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \TNW\Stripe\Gateway\Config\Config $stripeConfig,
        Config $paymentConfig,
        StripeAdapterFactory $stripeAdapterFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);

        $this->stripeConfig = $stripeConfig;
        $this->stripeAdapterFactory = $stripeAdapterFactory;
        $this->paymentConfig = $paymentConfig;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return StripeConfigProvider::CODE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentTitle()
    {
        return $this->getMethodConfigData('title');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalFields()
    {
        $result = [
            'credit_card_type' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'name' => $this->getPaymentCode() . '-cc-type',
                            'label' => __('Credit Card Type'),
                            'componentType' => Form\Field::NAME,
                            'formElement' => Form\Element\Select::NAME,
                            'dataScope' => 'cc_type',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-select',
                            'dataContainer' => $this->getPaymentCode() . '-cc-type',
                            'additionalClasses' => 'credit-card-type',
                            'sortOrder' => 10,
                            'options' => $this->getPaymentCcTypes(),
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                            ],
                            'validation' => [
                                'required-entry' => true,
                            ]
                        ],
                    ],
                ],
            ],
            'stripe_container' => [
                'arguments' => [
                    'data' => [
                        /* 'config' => [
                             'name' => 'stripe_container',
                             'label' => __('Credit Card Information'),
                             'template' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-container',
                             'componentType' => Container::NAME,
                             'title' => __('Credit Card Information'),
                             'additionalClasses' => 'field_without_legend',
                             'dataScope' => 'additional',
                             'sortOrder' => 20,
                             'required' => true,
                             'imports' => [
                                 'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                             ],
                       ],*/
                         "config" => [
                             "formElement" => "container",
                             "componentType" => "container",
                             'component' => 'Magento_Ui/js/form/components/html',
                             "label" => __("Custom Information"),
                             "required" => 0,
                             "sortOrder" => 1,
                             "content" => $this->layoutFactory->create()->createBlock(
                                 'TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Payments\Stripe'
                             )->toHtml(),
                         ]
                    ],
                ]
            ]
        ];
        /*'credit_card_number' => [
            'arguments' => [
                'data' => [
                    'config' => [
                        'name' => $this->getPaymentCode() . '-cc-number2',
                        'label' => __('Credit Card Number'),
                        'placeholder' => __('Credit card number'),
                        'componentType' => Form\Field::NAME,
                        'formElement' => Form\Element\Input::NAME,
                        'dataScope' => 'cc_number',
                        'dataType' => Form\Element\DataType\Text::NAME,
                        'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-input',
                        'additionalClasses' => 'credit-card-number',
                        'dataContainer' => $this->getPaymentCode() . '-cc-number2',
                        'sortOrder' => 20,
                        'imports' => [
                            'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                        ]
                    ],
                ],
            ],
        ],
        'exp_date_container' => [
            'arguments' => [
                'data' => [
                    'config' => [
                        'name' => $this->getPaymentCode() . '-cc-exp',
                        'label' => __('Expiration Date'),
                        'component' => 'TNW_Subscriptions/js/components/group',
                        'componentType' => Container::NAME,
                        'title' => __('Expiration Date'),
                        'additionalClasses' => 'field_without_legend',
                        'dataScope' => '',
                        'sortOrder' => 30,
                        'required' => true,
                        'imports' => [
                            'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                        ],
                    ],
                ],
            ],
            'children' => [
                'exp_date_month' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'name' => $this->getPaymentCode() . '-cc-month',
                                'label' => false,
                                'componentType' => Form\Field::NAME,
                                'formElement' => Form\Element\Input::NAME,
                                'dataScope' => 'cc_exp_month',
                                'dataType' => Form\Element\DataType\Text::NAME,
                                'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-input',
                                'dataContainer' => $this->getPaymentCode() . '-cc-month',
                                'additionalClasses' => 'control-label-up select month',
                                'sortOrder' => 10,
                            ],
                        ],
                    ],
                ],
                'exp_date_year' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'name' => $this->getPaymentCode() . '-cc-year',
                                'label' => false,
                                'componentType' => Form\Field::NAME,
                                'formElement' => Form\Element\Input::NAME,
                                'dataScope' => 'cc_exp_year',
                                'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-input',
                                'dataContainer' => $this->getPaymentCode() . '-cc-year',
                                'additionalClasses' => 'control-label-up select year',
                                'dataType' => Form\Element\DataType\Text::NAME,
                                'sortOrder' => 20,
                            ],
                        ],
                    ],
                ],
            ]
        ]
        ];

        if ($this->hasVerification()) {
        $result['credit_card_cvv'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'name' => $this->getPaymentCode() . '-cc-cvv',
                        'label' => __('Card Verification Number'),
                        'placeholder' => __('Credit verification number'),
                        'componentType' => Form\Field::NAME,
                        'formElement' => Form\Element\Input::NAME,
                        'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/input',
                        'dataContainer' => $this->getPaymentCode() . '-cc-cvv',
                        'dataScope' => 'cc_cid',
                        'dataType' => Form\Element\DataType\Text::NAME,
                        'additionalClasses' => 'payment-cvv',
                        'sortOrder' => 40,
                        'imports' => [
                            'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                        ]
                    ],
                ],
            ],
        ];
        }*/

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalConfig()
    {
        return [
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/stripe',
            'listens' => $this->getListens(),
            'dataContainer' => $this->getPaymentCode() . '-transparent-iframe',
            'code' => $this->getPaymentCode(),
            'sdkUrl' => $this->stripeConfig->getSdkUrl(),
            'publishableKey' => $this->stripeConfig->getPublishableKey(),
            'clientToken' => $this->getClientToken(),
            'useCvv' => $this->hasVerification(),
            'availableCardTypes' => $this->stripeConfig->getAvailableCardTypes(),
            'ccTypesMapper' => $this->stripeConfig->getCcTypesMapper(),
            'options' => [
                'formName' => $this->getPaymentFormName(),
            ],
            'imports' => [
                'changeVisibility' => "{$this->getFieldsetName()}.method:checked",
            ],
        ];
    }

    /**
     * Returns array of child elements.
     *
     * @return array
     */
    protected function getChildren()
    {
        $result = [
            'method' => $this->getField(),
        ];
        $fieldsetName = $this->getFieldsetName();
        $checkBoxName = $fieldsetName . '.method';
        $result['additional_fields'] = [
            'children' => $this->getAdditionalFields(),
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => \Magento\Ui\Component\Form\Fieldset::NAME,
                        'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/additional-fields-fieldset',
                        'template' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe',
                        'label' => false,
                        'visible' => false,
                        'dataScope' => 'additional',
                        'additionalClasses' => 'payment-additional-fieldset',
                        'collapsible' => false,
                        'opened' => true,
                        'imports' => [
                            'changeVisibility' => $checkBoxName . ':checked',
                        ],
                        'exports' => [
                            'visible' => $fieldsetName . ':checked',
                        ],
                    ],
                ],
            ],
        ];

        return $result;
    }

    /**
     * Generate a new client token if necessary
     * @return string
     */
    public function getClientToken()
    {
        return $this->stripeConfig->getPublishableKey();
    }

    /**
     * Returns list of available credit card types.
     *
     * @return array
     */
    private function getPaymentCcTypes()
    {
        $result[] = [
            'label' =>  __('Type'),
            'value' => ''
        ];

        $types = $this->paymentConfig->getCcTypes();
        $availableTypes = $this->stripeConfig->getAvailableCardTypes();

        if ($availableTypes) {
            foreach ($types as $code => $name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                } else {
                    $result[] = [
                        'value' => $code,
                        'label' => $name,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function modifyConfigData(array $configData)
    {
        $date = $this->getValidationDate();
        return array_merge(
            $configData,
            [
                $this->getPaymentCode() . '_start_on_month' => $date->format('m'),
                $this->getPaymentCode() . '_start_on_year' => $date->format('Y'),
            ]
        );
    }

    /**
     * Retrieves has verification configuration.
     *
     * @return bool
     */
    private function hasVerification()
    {
        return $this->stripeConfig->isCvvEnabled();
    }

    /**
     * Retrieves config data value by field name.
     *
     * @param string $fieldName
     * @return mixed
     */
    private function getMethodConfigData($fieldName)
    {
        return $this->stripeConfig->getValue($fieldName);
    }
}
