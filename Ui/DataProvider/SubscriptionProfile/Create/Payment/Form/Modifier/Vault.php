<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Create\Payment\Form\Modifier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Payment\Braintree\AdapterFactory;
use TNW\Subscriptions\Model\Payment\DataBuilder;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as OrderRelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as SubscriptionProfileManager;

/**
 * Class Vault modifier
 */
class Vault extends Base
{
    /**
     * @var string
     */
    private $tokensConfig = [];

    /**
     * @var null
     */
    private $tokensConfigProvider;

    /**
     * @var string
     */
    private $currentVaultMethod = 'vault';

    /**
     * @var array
     */
    private $vaultMethods = [];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @var CcConfig
     */
    private $ccConfig;

    /**
     * @var VaultConfigProvider
     */
    private $vaultConfigProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var array
     */
    private $currentProfilePublicHash = [];

    /**
     * @var mixed
     */
    private $braintreeConfig;

    /**
     * @var mixed
     */
    private $stripeConfig;

    /**
     * @var DataBuilder
     */
    private $dataBuilder;

    /**
     * @var AdapterFactory
     */
    private $braintreeAdapterFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SubscriptionProfileManager
     */
    private $subscriptionProfileManager;

    /**
     * Vault constructor.
     * @param ObjectManagerInterface $objectManager
     * @param CcConfig $ccConfig
     * @param VaultConfigProvider $vaultConfigProvider
     * @param Config $config
     * @param QuoteSessionInterface $session
     * @param SubscriptionProfileRepository $profileRepository
     * @param OrderRelationManager $relationManager
     * @param CartRepositoryInterface $cartRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param SessionManagerInterface $sessionManager
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param Manager $moduleManager
     * @param DataBuilder $dataBuilder
     * @param AdapterFactory $braintreeAdapterFactory
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param SubscriptionProfileManager $subscriptionProfileManager
     * @param string $tokensConfigClass
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CcConfig $ccConfig,
        VaultConfigProvider $vaultConfigProvider,
        Config $config,
        QuoteSessionInterface $session,
        SubscriptionProfileRepository $profileRepository,
        OrderRelationManager $relationManager,
        CartRepositoryInterface $cartRepository,
        ScopeConfigInterface $scopeConfig,
        SessionManagerInterface $sessionManager,
        PaymentTokenManagementInterface $paymentTokenManagement,
        Manager $moduleManager,
        DataBuilder $dataBuilder,
        AdapterFactory $braintreeAdapterFactory,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        SubscriptionProfileManager $subscriptionProfileManager,
        $tokensConfigClass = ''
    ) {
        parent::__construct($config, $session, $profileRepository, $relationManager, $cartRepository, $storeManager);
        if ($tokensConfigClass) {
            $this->tokensConfigProvider = $objectManager->get($tokensConfigClass);
        } else {
            $this->tokensConfigProvider = null;
        }
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->config = $config;
        $this->session = $session;
        $this->ccConfig = $ccConfig;
        $this->vaultConfigProvider = $vaultConfigProvider;
        $this->scopeConfig = $scopeConfig;
        $this->sessionManager = $sessionManager;
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->braintreeConfig = $objectManager->get(\PayPal\Braintree\Gateway\Config\Config::class);
        }
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->stripeConfig
                = $objectManager->get(\TNW\Stripe\Gateway\Config\Config::class);
        }
        $this->dataBuilder = $dataBuilder;
        $this->braintreeAdapterFactory = $braintreeAdapterFactory;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->subscriptionProfileManager = $subscriptionProfileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        if ($this->currentProfilePublicHash) {
            $data['payment'][$this->currentProfilePublicHash['method']]['additional']['publicHash']
                = $this->currentProfilePublicHash['value'];
        }
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $storeId = $this->getProfile()
            ? $this->getProfile()->getStoreId()
            : $this->session->getStoreId();

        if (!$storeId) {
            $profileOrder = $this->subscriptionProfileManager->getLastProfileOrder($this->getProfile());
            $storeId = $profileOrder->getStoreId();
        }

        $this->storeManager->setCurrentStore($storeId);
        foreach ($this->vaultConfigProvider->getConfig()['vault'] as $vaultCode => $enabledConfig) {
            if ($this->config->isPaymentMethodAvailableForSubscription(
                str_replace(['_cc_vault', '_vault'], '', $vaultCode),
                $storeId
            )
            ) {
                $this->vaultMethods[] = $vaultCode;
            }
        }
        if (empty($this->vaultMethods)) {
            return $meta;
        }
        $customerId = $this->session->getCustomerId();
        if ((!$this->sessionManager->getCustomerId() && $customerId)
            || $this->sessionManager->getCustomerId() != $customerId
        ) {
            $this->sessionManager->setCustomerId($customerId);
        }
        if ($this->getProfile() && $this->getProfile()->getCustomerId()) {
            $this->sessionManager->setCustomerId($this->getProfile()->getCustomerId());
        }
        if ($this->tokensConfigProvider) {
            switch (get_class($this->tokensConfigProvider)) {
                case \Magento\Vault\Model\Ui\TokensConfigProvider::class:
                    $this->processTokensConfigData($this->tokensConfigProvider->getConfig());
                    foreach ($this->vaultMethods as $method) {
                        if (empty($this->tokensConfig[$method])) {
                            continue;
                        }
                        $this->currentVaultMethod = $method;
                        $meta = array_replace_recursive(
                            $meta,
                            $this->getPaymentFields()
                        );
                    }
                    break;
                case \Magento\Vault\Model\Ui\Adminhtml\TokensConfigProvider::class:
                    foreach ($this->vaultMethods as $method) {
                        $this->tokensConfig[$method] = $this->tokensConfigProvider->getTokensComponents($method);
                        if (empty($this->tokensConfig[$method])) {
                            continue;
                        }
                        $this->currentVaultMethod = $method;

                        $meta = array_replace_recursive(
                            $meta,
                            $this->getPaymentFields()
                        );
                    }
                    break;
                default:
                    break;
            }
        }
        return $meta;
    }

    /**
     * @param $configData
     */
    protected function processTokensConfigData($configData)
    {
        if (isset($configData['payment']['vault']) && is_array($configData['payment']['vault'])) {
            foreach ($this->vaultMethods as $method) {
                foreach ($configData['payment']['vault'] as $code => $data) {
                    if (strpos($code, $method) !== false) {
                        $this->tokensConfig[$method][] = new DataObject($data);
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getAdditionalFields()
    {
        $cards = [];
        $paymentToken = null;
        if ($this->getProfile() && $this->getProfile()->getPayment()) {
            $paymentToken = $this->getProfile()->getPayment()->getPaymentToken();
        }
        if ($paymentToken) {
            try {
                $vaultToken = $this->paymentTokenManagement->getByGatewayToken(
                    $paymentToken,
                    $this->getPaymentMethodCodeByVaultCode($this->currentVaultMethod),
                    $this->getProfile()->getCustomerId()
                );
            } catch (\Exception $e) {
                $vaultToken = null;
            }
            if ($vaultToken) {
                $this->currentProfilePublicHash['value'] = $vaultToken->getPublicHash();
                $this->currentProfilePublicHash['method'] = $this->getProfile()->getPayment()->getEngineCode();
            }
        }
        foreach ($this->tokensConfig[$this->currentVaultMethod] as $ccToken) {
            $ccType = isset($ccToken->getConfig()['details']['type'])
                ? $ccToken->getConfig()['details']['type']
                : $ccToken->getConfig()['details']['cc_type'];
            $ccTypeLabel = $this->getCcTypeLabel($ccType);
            $maskedCC = isset($ccToken->getConfig()['details']['maskedCC'])
                ? $ccToken->getConfig()['details']['maskedCC']
                : $ccToken->getConfig()['details']['cc_last_4'];
            $expDate = isset($ccToken->getConfig()['details']['expirationDate'])
                ? str_replace("\\/", "/", $ccToken->getConfig()['details']['expirationDate'])
                : $ccToken->getConfig()['details']['cc_exp_month']
                    . '/'
                    . $ccToken->getConfig()['details']['cc_exp_year'];
            $ccTitle = $ccTypeLabel
                . ' ending '
                . $maskedCC
                . ' (expires: '
                . $expDate
                . ')';
            $pubHash = $ccToken->getConfig()['publicHash'];
            $checked = $this->currentProfilePublicHash && $this->currentProfilePublicHash['value'] == $pubHash;
            $cards[$pubHash] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'formElement' => Checkbox::NAME,
                            'componentType' => Field::NAME,
                            'prefer' => 'radio',
                            'description' => $ccTitle,
                            'value' => $pubHash,
                            'checked' => $checked,
                            'dataScope' => 'publicHash',
                            'elementTmpl' => 'TNW_Subscriptions/form/element/radio',
                            'validation' => [
                                'required-entry' => true
                            ],
                            'imports' => [
                                'visible' => $this->getFieldsetName() . '.additional_fields:visible',
                            ],
                        ],
                    ],
                ]
            ];
        }
        return $cards;
    }

    /**
     * @return array
     */
    protected function getAdditionalConfig()
    {
        if ($this->getPaymentCode() === 'braintree_cc_vault') {
            return [
                'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/braintree',
                'options' => [
                    'formName' => $this->getPaymentFormName(),
                ],
                'imports' => [
                    'changeVisibility' => "{$this->getFieldsetName()}.method:checked",
                ],
                'nonceUrl' => $this->getNonceRetrieveUrl(),
                'clientToken' => $this->getClientToken(),
                'three_d_enabled' => $this->braintreeConfig->isVerify3DSecure(),
                'thresholdAmount' => $this->braintreeConfig->getThresholdAmount(),
                'totalAmount' => $this->dataBuilder->getAmountByProfile($this->getProfile()),
                'specificCountries' => $this->braintreeConfig->get3DSecureSpecificCountries(),
                'useCvvVault' => $this->braintreeConfig->isCvvEnabledVault(),
            ];
        }
        if ($this->getPaymentCode() === 'tnw_stripe_vault') {
            if ($this->session->getFirstQuote() && !$this->getProfile()) {
                $totalAmount = $this->session->getFirstQuote()->collectTotals()->getGrandTotal();
            }
            return [
                'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/stripe',
                'options' => [
                    'formName' => $this->getPaymentFormName(),
                ],
                'createUrl' => $this->getCreatePaymentIntentUrl(),
                'sdkUrl' => $this->stripeConfig->getSdkUrl(),
                'stripe' => [
                    'publishableKey' => $this->stripeConfig->getPublishableKey(),
                ],
                'clientToken' => $this->stripeConfig->getPublishableKey(),
                'totalAmount' => $totalAmount ?? $this->dataBuilder->getAmountByProfile($this->getProfile()),
                'currency' => $this->getCurrencyCode()
            ];
        }
        return [
            'component' => 'TNW_Subscriptions/js/form/subscription-profile/payment/base',
            'options' => [
                'formName' => $this->getPaymentFormName(),
            ],
        ];
    }

    /**
     * @param $ccCode
     * @return mixed
     */
    protected function getCcTypeLabel($ccCode)
    {
        return $this->ccConfig->getCcAvailableTypes()[$ccCode];
    }

    /**
     * @return string
     */
    protected function getPaymentCode()
    {
        return $this->currentVaultMethod;
    }

    /**
     * @return string
     */
    protected function getPaymentTitle()
    {
        return $this->scopeConfig->getValue('payment/' . $this->getPaymentCode() . '/title');
    }

    /**
     * @param $vaultCode
     * @return mixed
     */
    private function getPaymentMethodCodeByVaultCode($vaultCode)
    {
        return str_replace(['_cc_vault', '_vault'], '', $vaultCode);
    }

    /**
     * Generate a new client token if necessary
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getClientToken()
    {
        if (empty($this->clientToken)) {
            $params = [];

            $merchantAccountId = $this->braintreeConfig->getMerchantAccountId();
            if (!empty($merchantAccountId)) {
                $params[PaymentDataBuilder::MERCHANT_ACCOUNT_ID] = $merchantAccountId;
            }

            $this->clientToken = $this->braintreeAdapterFactory->create()->generate($params);
        }

        return $this->clientToken;
    }

    /**
     * Get url to retrieve payment method nonce
     * @return string
     */
    private function getNonceRetrieveUrl()
    {
        return $this->urlBuilder->getUrl('braintree/payment/getnonce', ['_secure' => true]);
    }

    /**
     * @return string
     */
    private function getCreatePaymentIntentUrl()
    {
        return $this->urlBuilder->getUrl('tnw_stripe/paymentintent/create', ['_secure' => true]);
    }
}
