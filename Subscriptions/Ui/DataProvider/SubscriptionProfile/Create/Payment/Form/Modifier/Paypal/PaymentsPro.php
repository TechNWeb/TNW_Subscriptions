<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */


namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier\Paypal;

use Magento\Payment\Model\Config;
use Magento\Paypal\Model\Payflow\Transparent;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Field;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier\Base;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;

class PaymentsPro extends Base
{

    const SORT_ORDER = 20;

    private $paymentPro;
    private $context;
    private $paymentConfig;
    private $assetRepository;
    private $request;
    private $urlBuilder;

    /**
     * @param \TNW\Subscriptions\Model\Config $config
     * @param Context $context
     * @param Transparent $paymentPro
     * @param Config $paymentConfig
     * @param Repository $assetRepository
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        Context $context,
        Transparent $paymentPro,
        Config $paymentConfig,
        Repository $assetRepository,
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->context = $context;
        $this->paymentPro = $paymentPro;
        $this->paymentConfig = $paymentConfig;
        $this->assetRepository = $assetRepository;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;

        parent::__construct($config);

    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentCode()
    {
        return $this->paymentPro->getCode();
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaymentTitle()
    {
        return $this->paymentPro->getTitle();
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
                            'sortOrder' => 10,
                            'options' => $this->getPaymentCcTypes(),
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible'
                            ],
                            'validation' => [
                                'required-entry' => true,
                                'validate-cc-type-select' => $this->getPaymentCode() . '_cc_number'
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
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'dataScope' => 'cc_number',
                            'dataType' => Text::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/input',
                            'dataContainer' => $this->getPaymentCode() . '-cc-number',
                            'sortOrder' => 20,
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible'
                            ],
                            'validation' => [
                                'required-number' => true,
                                'validate-cc-number' => $this->getPaymentCode() . '_cc_type',
                                'validate-cc-type' => $this->getPaymentCode() . '_cc_type',
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
                            'component' => 'Magento_Ui/js/form/components/group',
                            'componentType' => Container::NAME,
                            'title' => __('Expiration Date'),
                            'additionalClasses' => 'admin_field_without_legend',
                            'dataScope' => '',
                            'sortOrder' => 30,
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
                                    'formElement' => Select::NAME,
                                    'dataScope' => 'cc_exp_month',
                                    'dataType' => Text::NAME,
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/select',
                                    'dataContainer' => $this->getPaymentCode() . '-cc-month',
                                    'additionalClasses' => 'admin__control-label-up select',
                                    'sortOrder' => 10,
                                    'options' => $this->getCcMonths(),
                                    'imports' => [
                                        'visible' => $this->getFieldsetName() . '.additional_fields:visible'
                                    ],
                                    'validation' => [
                                        'required-entry' => true,
                                        'validate-cc-exp' => $this->getPaymentCode() . '_expiration_yr'
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
                                    'formElement' => Select::NAME,
                                    'dataScope' => 'cc_exp_year',
                                    'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/select',
                                    'dataContainer' => $this->getPaymentCode() . '-cc-year',
                                    'additionalClasses' => 'admin__control-label-up select',
                                    'dataType' => Text::NAME,
                                    'sortOrder' => 20,
                                    'options' => $this->getCcYears(),
                                    'imports' => [
                                        'visible' => $this->getFieldsetName() . '.additional_fields:visible'
                                    ],
                                    'validation' => [
                                        'required-entry' => true
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
                            'name' => '',
                            'componentType' => Field::NAME,
                            'formElement' => Input::NAME,
                            'elementTmpl' => 'TNW_Subscriptions/form/subscription-profile/payment/input',
                            'dataContainer' => $this->getPaymentCode() . '-cc-cvv',
                            'dataScope' => 'cc_cid',
                            'dataType' => Text::NAME,
                            'additionalClasses' => 'payment-cvv',
                            'sortOrder' => 40,
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible'
                            ],
                            'validation' => [
                                'required-number' => true,
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
        return [
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/fieldset',
            'listens'=> $this->getListens(),
            'dataContainer' => $this->getPaymentCode() . '-transparent-iframe',
            'iframeSrc' => $this->context->getEscaper()->escapeUrl($this->getViewFileUrl('blank.html')),
            'options' => [
                'gateway' => $this->getPaymentCode(),
                'dateDelim' => $this->context->getEscaper()->escapeHtml($this->getDateDelim()),
                'cardFieldsMap' => $this->getCardFieldsMap(),
                'orderSaveUrl' => $this->context->getEscaper()->escapeUrl($this->getOrderUrl()),
                'cgiUrl' => $this->context->getEscaper()->escapeUrl($this->getCgiUrl()),
                'expireYearLength' => $this->context->getEscaper()->escapeHtml($this->getMethodConfigData('cc_year_length')),
                'formName' => $this->getPaymentFormName(),
            ]
        ];
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
        $availableTypes = $this->paymentPro->getConfigData('cctypes');

        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach ($types as $code => $name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                } else {
                    $result[] = [
                        'value' => $code,
                        'label' => $name
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
     * Retrieves has verification configuration.
     *
     * @return bool
     */
    private function hasVerification()
    {
        return (bool)$this->paymentPro->getConfigData('useccv');
    }

    /**
     * Retrieves url of a view file.
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    private function getViewFileUrl($fileId, array $params = [])
    {
        $result = false;
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            $result = $this->assetRepository->getUrlWithParams($fileId, $params);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->context->throwException($e->getMessage());
        }

        return $result;
    }

    /**
     * Gets delimiter for date.
     *
     * @return string
     */
    private function getDateDelim()
    {
        return $this->getMethodConfigData('date_delim');
    }

    /**
     * Gets map of cc_code, cc_num, cc_expdate for gateway.
     * Returns json formatted string.
     *
     * @return string
     */
    private function getCardFieldsMap()
    {
        $keys = ['cccvv', 'ccexpdate', 'ccnum'];
        $ccfields = array_combine($keys, explode(',', $this->getMethodConfigData('ccfields')));
        return json_encode($ccfields);
    }

    /**
     * Retrieves place order url on front.
     *
     * @return string
     */
    private function getOrderUrl()
    {
        return $this->urlBuilder->getUrl(
            'tnw_subscriptions/subscriptionprofile_create_paypal/requestSecureToken',
            [
                '_secure' => $this->request->isSecure(),
                SummaryInsertForm::FORM_DATA_KEY => $this->getProfileId()
            ]
        );
    }

    /**
     * Retrieves gateway url.
     *
     * @return string
     */
    private function getCgiUrl()
    {
        return (bool)$this->getMethodConfigData('sandbox_flag')
            ? $this->getMethodConfigData('cgi_url_test_mode')
            : $this->getMethodConfigData('cgi_url');
    }

    /**
     * Retrieves config data value by field name.
     *
     * @param string $fieldName
     * @return mixed
     */
    private function getMethodConfigData($fieldName)
    {
        if ($this->paymentPro instanceof TransparentInterface) {
            $result = $this->paymentPro->getConfigInterface()->getValue($fieldName);
        }else{
            $result = $this->paymentPro->getConfigData($fieldName);
        }
        return $result;
    }
}
