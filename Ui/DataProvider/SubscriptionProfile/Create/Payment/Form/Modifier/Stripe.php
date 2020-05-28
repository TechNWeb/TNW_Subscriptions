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
        Config $paymentConfig,
        LayoutFactory $layoutFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository);
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            /*$this->stripeAdapterFactory
                = $objectManager->get("TNW\Stripe\Model\Adapter\StripeAdapterFactory");*/
            $this->stripeConfig
                = $objectManager->get("TNW\Stripe\Gateway\Config\Config");
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
            'stripe_container' => [
                'arguments' => [
                    'data' => [
                         "config" => [
                             "formElement" => "container",
                             "componentType" => "container",
                             'component' => 'Magento_Ui/js/form/components/html',
                             "label" => __("Custom Information"),
                             "required" => 0,
                             "sortOrder" => 1,
                             "content" => $this->layoutFactory->create()->createBlock(
                                 "TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Payments\Stripe"
                             )->toHtml(),
                         ]
                    ],
                ]
            ]
        ];

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
     * @inheritdoc
     * @param $requestData
     * @return Stripe
     * @throws PaymentException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function processProfileByRequestData($requestData)
    {
        if (empty($requestData['payment'][$this->getPaymentMethodCode()]['method'])) {
            return $this;
        }

        $customer = $this->getProfile()->getCustomer();
        if (!$customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            return $this;
        }

        /** @var string[] $additionalData */
        $additionalData = $requestData['payment'][$this->getPaymentMethodCode()]['additional'];
        $paymentData = $requestData['payment'][$this->getPaymentMethodCode()];
        $transfer = $this->transferFactory->create([
            'firstName' => $customer->getFirstname(),
            'lastName' => $customer->getLastname(),
            'email' => $customer->getEmail()
        ]);

        $response = $this->transactionCustomer->placeRequest($transfer);
        if ($response['object'] instanceof \Stripe\Error\Card) {
            $errors = [];
            foreach ($response->errors->deepAll() as $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new PaymentException(__('Stripe message: %1', implode(', ', $errors)));
        }
        /** @var \Stripe\Card $paymentMethod */
        $this->getProfile()->getPayment()
            ->setPaymentToken($paymentData['client_secret'])
            ->setEncodedPaymentAdditionalInfo([
                OrderPaymentInterface::CC_TYPE => $additionalData['cc_type'],
                OrderPaymentInterface::CC_LAST_4 => $paymentData['cc_last_4'],
                OrderPaymentInterface::CC_EXP_MONTH => $additionalData['cc_exp_month'],
                OrderPaymentInterface::CC_EXP_YEAR => $additionalData['cc_exp_year'],
            ]);

        return $this;
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
