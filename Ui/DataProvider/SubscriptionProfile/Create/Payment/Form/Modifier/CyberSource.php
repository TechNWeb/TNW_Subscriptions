<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use \Magento\Framework\UrlInterface;
use Magento\Payment\Model\Config;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

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
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var \CyberSource\Core\Block\Fingerprint
     */
    private $fingerprintBlock;

    /**
     * CyberSource constructor.
     * @param \TNW\Subscriptions\Model\Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param Config $paymentConfig
     * @param UrlInterface $urlBuilder
     * @param \CyberSource\Core\Block\Fingerprint $fingerprintBlock
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Config $paymentConfig,
        UrlInterface $urlBuilder,
        \CyberSource\Core\Block\Fingerprint $fingerprintBlock
    ) {
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
        if ($moduleManager->isEnabled("CyberSource_SecureAcceptance")) {
            $this->cybersourceConfig = $objectManager->get("CyberSource\SecureAcceptance\Gateway\Config\Config");
        }
        $this->paymentConfig = $paymentConfig;
        $this->urlBuilder = $urlBuilder;
        $this->fingerprintBlock = $fingerprintBlock;
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
        $fingerprint = $this->fingerprintBlock
            ->setTemplate('CyberSource_Core::fingerprint.phtml')->toHtml();
        $result = [
            'fingerprint' => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Container::NAME,
                            'component' => 'Magento_Ui/js/form/components/html',
                            'content' => $fingerprint,
                        ]
                    ]
                ]
            ],
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
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/input',
                            'additionalClasses' => 'credit-card-number',
                            'dataContainer' => $this->getPaymentCode() . '-cc-number',
                            'sortOrder' => 20,
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                            ],
                            'validation' => [
                                'required-entry' => true,
                                'required-number' => true,
                                'validate-cc-number' => $this->getPaymentCode() . '_cc_type',
                                'validate-cc-type' => $this->getPaymentCode() . '_cc_type',
                            ],
                            'valueUpdate' => 'keyup'
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
                                    'formElement' => Form\Element\Select::NAME,
                                    'options' => $this->getCcMonths(),
                                    'dataScope' => 'cc_exp_month',
                                    'dataType' => Form\Element\DataType\Text::NAME,
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/select',
                                    'dataContainer' => $this->getPaymentCode() . '-cc-month',
                                    'additionalClasses' => 'control-label-up select month',
                                    'sortOrder' => 10,
                                    'validation' => [
                                        'required-entry' => true,
                                        'subscription-validate-cc-exp-month' => $this->getPaymentCode(),
                                    ],
                                    'imports' => [
                                        'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                                    ],
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
                                    'formElement' => Form\Element\Select::NAME,
                                    'options' => $this->getCcYears(),
                                    'dataScope' => 'cc_exp_year',
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/select',
                                    'dataContainer' => $this->getPaymentCode() . '-cc-year',
                                    'additionalClasses' => 'control-label-up select year',
                                    'dataType' => Form\Element\DataType\Text::NAME,
                                    'sortOrder' => 20,
                                    'validation' => [
                                        'required-entry' => true,
                                        'subscription-validate-cc-exp-year' => $this->getPaymentCode(),
                                    ],
                                    'imports' => [
                                        'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                                    ],
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
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/input',
                            'dataContainer' => $this->getPaymentCode() . '-cc-cvv',
                            'dataScope' => 'cc_cid',
                            'dataType' => Form\Element\DataType\Text::NAME,
                            'additionalClasses' => 'payment-cvv',
                            'sortOrder' => 40,
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                            ],
                            'validation' => [
                                'required-number' => true,
                                'required-entry' => true,
                                'validate-cc-cvn' => $this->getPaymentCode() . '_cc_type'
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
        if ($this->cybersourceConfig->isTestMode()) {
            $configServiceUrl = $this->cybersourceConfig->getSopServiceUrlTest();
        } else {
            $configServiceUrl = $this->cybersourceConfig->getSopServiceUrl();
        }
        $tokenCreateUrl = $configServiceUrl . '/silent/embedded/token/create';
        $loadSilentDataUrl = ($this->urlBuilder instanceof \Magento\Backend\Model\UrlInterface)
            ? $this->urlBuilder->getUrl('tnw_subscriptions/secureAcceptance/TokenRequest')
            : $this->urlBuilder->getUrl('cybersource/index/loadSilentData');
        return [
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/cybersource_sop',
            'listens' => $this->getListens(),
            'dataContainer' => $this->getPaymentCode() . '-transparent-iframe',
            'code' => $this->getPaymentCode(),
            'sopServiceUrl' => $tokenCreateUrl,
            'loadSilentDataUrl' => $loadSilentDataUrl,
            'useCvv' => $this->hasVerification(),
            'availableCardTypes' => explode(',', $this->cybersourceConfig->getCcTypes()),
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
     * Retrieves credit card expire months.
     *
     * @return array
     */
    private function getCcMonths()
    {
        $result[] = [
            'label' =>  __('Month'),
            'value' => ''
        ];
        foreach ($this->paymentConfig->getMonths() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $result;
    }

    /**
     * Retrieves credit card expire years
     *
     * @return array
     */
    private function getCcYears()
    {
        $result[] = [
            'label' =>  __('Year'),
            'value' => ''
        ];
        foreach ($this->paymentConfig->getYears() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => (string)$label
            ];
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
        return !$this->cybersourceConfig->getIgnoreCvn();
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
