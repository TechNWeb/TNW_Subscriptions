<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Payment\Model\Config;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use Magento\Braintree\Model\Ui\ConfigProvider as BraintreeConfigProvider;

class CyberSource extends Base
{
    /**
     *
     */
    const SORT_ORDER = 35;

    /**
     * @var Config
     */
    private $paymentConfig;

    private $cybersourceConfig;

    /**
     * @var string
     */
    private $clientToken = '';

    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Config $paymentConfig
    ) {
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
        if ($moduleManager->isEnabled("CyberSource_SecureAcceptance")) {
            $this->cybersourceConfig = $objectManager->get("CyberSource\SecureAcceptance\Gateway\Config\Config");
        }
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return 'chcybersource';
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
                            'label' => __('Credit Card Type'),
                            'componentType' => Form\Field::NAME,
                            'formElement' => Form\Element\Select::NAME,
                            'dataScope' => 'cc_type',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/select',
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
            'credit_card_number' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Credit Card Number'),
                            'placeholder' => __('Credit card number'),
                            'componentType' => Form\Field::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'dataScope' => 'cc_number',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree-input',
                            'additionalClasses' => 'credit-card-number',
                            'dataContainer' => $this->getPaymentCode() . '-cc-number',
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
                                    'label' => false,
                                    'componentType' => Form\Field::NAME,
                                    'formElement' => Form\Element\Input::NAME,
                                    'dataScope' => 'cc_exp_month',
                                    'dataType' => Form\Element\DataType\Text::NAME,
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree-input',
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
                                    'label' => false,
                                    'componentType' => Form\Field::NAME,
                                    'formElement' => Form\Element\Input::NAME,
                                    'dataScope' => 'cc_exp_year',
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree-input',
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
                            'label' => __('Card Verification Number'),
                            'placeholder' => __('Credit verification number'),
                            'name' => '',
                            'componentType' => Form\Field::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree-input',
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
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalConfig()
    {
        return [
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/braintree',
            'listens' => $this->getListens(),
            'dataContainer' => $this->getPaymentCode() . '-transparent-iframe',
            'code' => $this->getPaymentCode(),
            'sdkUrl' => $this->cybersourceConfig->getSopServiceUrl(),
            'clientToken' => $this->getClientToken(),
            'useCvv' => $this->hasVerification(),
            'availableCardTypes' => $this->cybersourceConfig->getCcTypes(),
            'ccTypesMapper' => array_flip([
                'VI' => 'visa',
                'MC' => 'masterCard',
                'AE' => 'amex',
                'DI' => 'discover',
                'JCB' => 'jcb'
            ]),
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
                        'template' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree',
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
        return $this->clientToken;
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
        $availableTypes = explode(',', $this->cybersourceConfig->getCcTypes());

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
        return $this->cybersourceConfig->isCvvEnabled();
    }

    /**
     * Retrieves config data value by field name.
     *
     * @param string $fieldName
     * @return mixed
     */
    private function getMethodConfigData($fieldName)
    {
        return $this->cybersourceConfig->getValue($fieldName);
    }
}
