<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Payment\Model\Config;
use Magento\Ui\Component\Form;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use Magento\Framework\View\LayoutFactory;

/**
 * Stripe payment methods form modifier.
 */
class Stripe extends Base
{
    /**
     * Sort Order value
     */
    const SORT_ORDER = 25;

    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @var mixed
     */
    private $stripeConfig;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * Stripe constructor.
     * @param \TNW\Subscriptions\Model\Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param Config $paymentConfig
     * @param LayoutFactory $layoutFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        Config $paymentConfig,
        LayoutFactory $layoutFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->stripeConfig
                = $objectManager->get(\TNW\Stripe\Gateway\Config\Config::class);
        }

        $this->paymentConfig = $paymentConfig;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return 'tnw_stripe';
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
                            'dataContainer' => $this->getPaymentCode() . '-cc_type',
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
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-input',
                            'additionalClasses' => 'credit-card-number admin__field-medium',
                            'dataContainer' => $this->getPaymentCode() . '-cc_number',
                            'sortOrder' => 20,
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                            ]
                        ],
                    ],
                ],
            ],
            'exp_date' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Expiration Date'),
                            'componentType' => Form\Field::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'dataScope' => 'cc_exp',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-input',
                            'dataContainer' => $this->getPaymentCode() . '-cc_exp',
                            'additionalClasses' => 'admin__field-small',
                            'sortOrder' => 30,
                        ],
                    ],
                ],
            ],
        ];
        if ($this->hasVerification()) {
            $result['credit_card_cvv'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Card Verification Number'),
                            'name' => '',
                            'componentType' => Form\Field::NAME,
                            'formElement' => Form\Element\Input::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/stripe-input',
                            'dataContainer' => $this->getPaymentCode() . '-cc_cid',
                            'dataScope' => 'cc_cid',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'additionalClasses' => 'payment-cvv admin__field-x-small',
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
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/stripe',
            'listens' => $this->getListens(),
            'dataContainer' => $this->getPaymentCode() . '-transparent-iframe',
            'code' => $this->getPaymentCode(),
            'sdkUrl' => $this->stripeConfig->getSdkUrl(),
            'stripe' => [
                'publishableKey' => $this->stripeConfig->getPublishableKey(),
            ],
            'clientToken' => $this->getClientToken(),
            'useCvv' => $this->hasVerification(),
            'availableCardTypes' => $this->stripeConfig->getAvailableCardTypes(),
            'ccTypesMapper' => $this->stripeConfig->getCcTypesMapper(),
            'options' => [
                'formName' => $this->getPaymentFormName(),
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
                        'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/'
                            . 'additional-fields-fieldset',
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
