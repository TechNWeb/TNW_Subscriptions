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
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Form\Modifier\SummaryInsertForm;
use Magento\Braintree\Model\Ui\ConfigProvider as BraintreeConfigProvider;

/**
 * Braintree payment methods form modifier.
 */
class Braintree extends Base
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
     * @var Repository
     */
    private $assetRepository;

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
     * Braintree constructor.
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository
     * @param \Magento\Braintree\Gateway\Config\Config $braintreeConfig
     * @param \Magento\Braintree\Model\Adapter\BraintreeAdapter $braintreeAdapter
     * @param Context $context
     * @param Config $paymentConfig
     * @param Repository $assetRepository
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\SubscriptionProfileRepository $profileRepository,
        \Magento\Braintree\Gateway\Config\Config $braintreeConfig,
        \Magento\Braintree\Model\Adapter\BraintreeAdapter $braintreeAdapter,
        Context $context,
        Config $paymentConfig,
        Repository $assetRepository,
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        parent::__construct($config, $session, $profileRepository);

        $this->context = $context;
        $this->braintreeConfig = $braintreeConfig;
        $this->braintreeAdapter = $braintreeAdapter;
        $this->paymentConfig = $paymentConfig;
        $this->assetRepository = $assetRepository;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        $data = parent::modifyData($data);

        $additionalInfo = $this->getProfile()
            ? $this->getProfile()->getDecodedPaymentAdditionalInfo()
            : [];

        if (!empty($additionalInfo['cc_type'])) {
            $data['payment'][$this->getPaymentCode()]['additional']['cc_type']
                = $additionalInfo['cc_type'];
        }

        return $data;
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
            'options' => [
                'orderSaveUrl' => $this->context->getEscaper()->escapeUrl($this->getOrderUrl()),
                'expireYearLength' => $this->context->getEscaper()->escapeHtml($this->getMethodConfigData('cc_year_length')),
                'formName' => $this->getPaymentFormName(),
            ],
            'imports' => [
                'changeVisibility' => "{$this->getFieldsetName()}.method:checked"
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
                            'changeVisibility' => $checkBoxName . ':checked'
                        ],
                        'exports' => [
                            'visible' => $fieldsetName . ':checked'
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
        $result = [];
        $types = $this->paymentConfig->getCcTypes();
        $availableTypes = $this->braintreeConfig->getAvailableCardTypes();

        if ($availableTypes) {
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
            'label' => __('Month'),
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
            'label' => __('Year'),
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
            'tnw_subscriptions/paypal/requestSecureToken',
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
