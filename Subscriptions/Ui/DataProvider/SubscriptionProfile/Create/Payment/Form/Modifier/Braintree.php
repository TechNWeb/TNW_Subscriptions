<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Payment\Model\Config;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;
use TNW\Subscriptions\Model\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use Magento\Braintree\Model\Ui\ConfigProvider as BraintreeConfigProvider;

/**
 * Braintree payment methods form modifier.
 */
class Braintree extends Base implements PaymentModifierInterface
{
    const SORT_ORDER = 25;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @var \Magento\Braintree\Gateway\Config\Config
     */
    private $braintreeConfig;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Braintree\Model\Adapter\BraintreeAdapter
     */
    private $braintreeAdapter;

    /**
     * @var string
     */
    private $clientToken = '';

    /**
     * @param \TNW\Subscriptions\Model\Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param \Magento\Braintree\Gateway\Config\Config $braintreeConfig
     * @param \Magento\Braintree\Model\Adapter\BraintreeAdapter $braintreeAdapter
     * @param Context $context
     * @param Config $paymentConfig
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        \Magento\Braintree\Gateway\Config\Config $braintreeConfig,
        \Magento\Braintree\Model\Adapter\BraintreeAdapter $braintreeAdapter,
        Context $context,
        Config $paymentConfig,
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->context = $context;
        $this->braintreeConfig = $braintreeConfig;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->paymentConfig = $paymentConfig;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($config, $session, $profileRepository, $relationManager);
    }


    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return BraintreeConfigProvider::CODE;
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
                            'componentType' => Field::NAME,
                            'formElement' => Select::NAME,
                            'dataScope' => 'cc_type',
                            'dataType' => Text::NAME,
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
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'dataScope' => 'cc_number',
                            'dataType' => Text::NAME,
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
                                    'componentType' => Field::NAME,
                                    'formElement' => Input::NAME,
                                    'dataScope' => 'cc_exp_month',
                                    'dataType' => Text::NAME,
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree-input',
                                    'dataContainer' => $this->getPaymentCode() . '-cc-month',
                                    'additionalClasses' => 'control-label-up select month',
                                    'sortOrder' => 10,
                                    'validation' => [
                                        'required-entry' => true,
                                        'subscription-validate-cc-exp-month' => $this->getPaymentCode(),
                                    ]
                                ],
                            ],
                        ],
                    ],
                    'exp_date_year' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => false,
                                    'componentType' => Field::NAME,
                                    'formElement' => Input::NAME,
                                    'dataScope' => 'cc_exp_year',
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree-input',
                                    'dataContainer' => $this->getPaymentCode() . '-cc-year',
                                    'additionalClasses' => 'control-label-up select year',
                                    'dataType' => Text::NAME,
                                    'sortOrder' => 20,
                                    'validation' => [
                                        'required-entry' => true,
                                        'subscription-validate-cc-exp-month' => $this->getPaymentCode(),
                                    ]
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
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/braintree-input',
                            'dataContainer' => $this->getPaymentCode() . '-cc-cvv',
                            'dataScope' => 'cc_cid',
                            'dataType' => Text::NAME,
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
            'sdkUrl' => $this->braintreeConfig->getSdkUrl(),
            'clientToken' => $this->getClientToken(),
            'useCvv' => $this->hasVerification(),
            'availableCardTypes' => $this->braintreeConfig->getAvailableCardTypes(),
            'ccTypesMapper' => $this->braintreeConfig->getCcTypesMapper(),
            'options' => [
                'orderSaveUrl' => $this->context->getEscaper()->escapeUrl($this->getOrderUrl()),
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
        if (empty($this->clientToken)) {
            $params = [];

            $merchantAccountId = $this->braintreeConfig->getMerchantAccountId();
            if (!empty($merchantAccountId)) {
                $params[\Magento\Braintree\Gateway\Request\PaymentDataBuilder::MERCHANT_ACCOUNT_ID] = $merchantAccountId;
            }

            $this->clientToken = $this->braintreeAdapter->generate($params);
        }

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
        $availableTypes = $this->braintreeConfig->getAvailableCardTypes();

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
        return $this->braintreeConfig->isCvvEnabled();
    }

    /**
     * Retrieves place order url on front.
     *
     * @return string
     */
    private function getOrderUrl()
    {
        $routeParams = [
            '_secure' => $this->request->isSecure(),
        ];

        if (null !== $this->getProfileId()) {
            $routeParams[SummaryInsertForm::FORM_DATA_KEY] = $this->getProfileId();
        }

        return $this->urlBuilder->getUrl(
            'tnw_subscriptions/braintree/requestSecureToken',
            $routeParams
        );
    }

    /**
     * Retrieves config data value by field name.
     *
     * @param string $fieldName
     * @return mixed
     */
    private function getMethodConfigData($fieldName)
    {
        return $this->braintreeConfig->getValue($fieldName);
    }
}
