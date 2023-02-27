<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\ViewModel\Subscription;

use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\ReBillInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface as SubscriptionUrlBuilderInterface;
use TNW\Subscriptions\Model\Payment\Braintree\AdapterFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Model\SubscriptionProfile\ReBillRepository;

/**
 * View model for Verify and Re-bill processing page
 */
class QueueProcess implements ArgumentInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * @var ReBillRepository
     */
    private $reBillRepository;

    /**
     * @var SubscriptionUrlBuilderInterface
     */
    private $urlBuilder;

    /**
     * @var ReBillInterface
     */
    private $rebill;

    /**
     * @var Quote
     */
    private $tempQuote;

    /**
     * @var AddressConfig
     */
    private $addressConfig;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var SubscriptionProfileInterface
     */
    private $profile;

    /**
     * @var PaymentConfig
     */
    private $paymentConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var mixed
     */
    private $braintreeConfig;

    /**
     * @var mixed
     */
    private $stripeConfig;

    /**
     * @var AdapterFactory
     */
    private $braintreeAdapterFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * QueueProcess constructor.
     * @param RequestInterface $request
     * @param SerializerInterface $serializer
     * @param Manager $profileManager
     * @param ReBillRepository $reBillRepository
     * @param SubscriptionUrlBuilderInterface $urlBuilder
     * @param UrlInterface $url
     * @param AddressConfig $addressConfig
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param PaymentConfig $paymentConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param ModuleManager $moduleManager
     * @param AdapterFactory $braintreeAdapterFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        SerializerInterface $serializer,
        Manager $profileManager,
        ReBillRepository $reBillRepository,
        SubscriptionUrlBuilderInterface $urlBuilder,
        UrlInterface $url,
        AddressConfig $addressConfig,
        SubscriptionProfileRepositoryInterface $profileRepository,
        PaymentConfig $paymentConfig,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        PaymentTokenManagementInterface $paymentTokenManagement,
        ModuleManager $moduleManager,
        AdapterFactory $braintreeAdapterFactory,
        PriceCurrencyInterface $priceCurrency,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->serializer = $serializer;
        $this->profileManager = $profileManager;
        $this->reBillRepository = $reBillRepository;
        $this->urlBuilder = $urlBuilder;
        $this->addressConfig = $addressConfig;
        $this->profileRepository = $profileRepository;
        $this->paymentConfig = $paymentConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->braintreeAdapterFactory = $braintreeAdapterFactory;
        $this->url = $url;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->braintreeConfig = $objectManager->get(\PayPal\Braintree\Gateway\Config\Config::class);
        }
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->stripeConfig
                = $objectManager->get(\TNW\Stripe\Gateway\Config\Config::class);
        }
    }

    /**
     * Get token from request
     *
     * @return string
     */
    private function getToken()
    {
        return $this->request->getParam('token');
    }

    /**
     * Get Json config for queue-process component
     *
     * @return bool|string
     */
    public function getJsConfig()
    {
        if ($this->getProfile()->getPayment()->getEngineCode() === 'braintree_cc_vault') {
            try {
                return $this->serializer->serialize(
                    [
                        'component' => 'TNW_Subscriptions/js/components/rebill/queue-process-braintree',
                        'config' => [
                            'nonceUrl' => $this->getBraintreeNonceRetrieveUrl(),
                            'clientToken' => $this->getBraintreeClientToken(),
                            'three_d_enabled' => $this->braintreeConfig->isVerify3DSecure(),
                            'thresholdAmount' => 0,
                            'totalAmount' => $this->getTempQuote()->getGrandTotal(),
                            'publicHash' => $this->getVaultedCardPublicHash(),
                            'specificCountries' => [],
                            'useCvvVault' => $this->braintreeConfig->isCvvEnabledVault(),
                            'processUrl' => $this->getProcessUrl(),
                            'token' => $this->getToken()
                        ]
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->critical($e);
                return '{}';
            }
        } elseif ($this->getProfile()->getPayment()->getEngineCode() === 'tnw_stripe_vault') {
            try {
                return $this->serializer->serialize(
                    [
                        'component' => 'TNW_Subscriptions/js/components/rebill/queue-process-tnw-stripe',
                        'config' => [
                            'createUrl' => $this->getCreatePaymentIntentUrl(),
                            'sdkUrl' => $this->stripeConfig->getSdkUrl(),
                            'stripe' => [
                                'publishableKey' => $this->stripeConfig->getPublishableKey(
                                    $this->getProfile()->getStoreId()
                                ),
                            ],
                            'totalAmount' => $this->getTempQuote()->getGrandTotal(),
                            'currency' => $this->getTempQuote()->getCurrency()->getQuoteCurrencyCode(),
                            'publicHash' => $this->getVaultedCardPublicHash(),
                            'processUrl' => $this->getProcessUrl(),
                            'token' => $this->getToken()
                        ]
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->critical($e);
                return '{}';
            }
        }
        return '{}';
    }

    /**
     * Get rebill object
     *
     * @return ReBillInterface|bool
     */
    private function getRebill()
    {
        if (!$this->rebill) {
            try {
                $this->rebill = $this->reBillRepository->getByToken($this->getToken());
            } catch (NoSuchEntityException $e) {
                $this->logger->critical($e);
                return false;
            }
        }
        return $this->rebill;
    }

    /**
     * Get profile links by ids
     *
     * @return string
     */
    public function getProfileLinks()
    {
        $ids = $this->getRebill()->getSubscriptionProfiles();
        $result = [];
        foreach ($ids as $id) {
            $result[] = $this->urlBuilder->getEditHtmlLink(
                $id,
                $this->profileRepository->getById($id)->getStoreId(),
                true
            );
        }
        return implode(', ', $result);
    }

    /**
     * @return Quote|bool
     */
    private function getTempQuote()
    {
        if (!$this->tempQuote) {
            try {
                $this->tempQuote = $this->profileManager->getTempQuoteByProfileIds(
                    $this->getRebill()->getSubscriptionProfiles()
                );
            } catch (\Exception $e) {
                $this->logger->critical($e);
                return false;
            }
        }
        return $this->tempQuote;
    }

    /**
     * @param string $addressType
     * @return mixed
     */
    public function getAddressHtml($addressType = 'billing')
    {
        $addressData = $addressType == 'shipping'
            ? $this->getTempQuote()->getShippingAddress()->getData()
            : $this->getTempQuote()->getBillingAddress()->getData();
        $renderer = $this->addressConfig->getFormatByCode('html')->getRenderer();
        return $renderer->renderArray($addressData);
    }

    /**
     * @return bool
     */
    public function isQuoteVirtual()
    {
        return $this->getTempQuote()->isVirtual();
    }

    /**
     * @return string
     */
    public function getShippingMethodHtml()
    {
        return $this->getTempQuote()->getShippingAddress()->getShippingDescription();
    }

    /**
     * @return SubscriptionProfileInterface|bool
     */
    private function getProfile()
    {
        if (!$this->profile) {
            try {
                $this->profile = $this->profileRepository->getById($this->getRebill()->getSubscriptionProfiles()[0]);
            } catch (NoSuchEntityException $e) {
                $this->logger->critical($e);
                return false;
            }
        }
        return $this->profile;
    }

    /**
     * @return array
     */
    private function getPaymentDetails()
    {
        return $this->getProfile()->getPayment()->getDecodedPaymentAdditionalInfo();
    }

    /**
     * @return string
     */
    public function getVaultCardDescription()
    {
        $result = [];
        $details = $this->getPaymentDetails();
        $ccTypes = $this->paymentConfig->getCcTypes();
        if (isset($ccTypes[$details['cc_type']])) {
            $result[] = $ccTypes[$details['cc_type']];
        }
        if (isset($details['cc_last_4'])) {
            $result[] = __('ending ') . $details['cc_last_4'];
        }
        if (isset($details['cc_exp_month']) && isset($details['cc_exp_year'])) {
            $ccExpMonth = $details['cc_exp_month'];
            $ccExpYear = $details['cc_exp_year'];
            $result[] = "( expires: {$ccExpMonth}/{$ccExpYear} )";
        }
        return implode(' ', $result);
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getPaymentMethodTitle()
    {
        return $this->scopeConfig->getValue(
            'payment/' . $this->getProfile()->getPayment()->getEngineCode() . '/title',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     * @throws InputException
     */
    private function getBraintreeClientToken()
    {
        $params = [];
        $merchantAccountId = $this->braintreeConfig->getMerchantAccountId();
        if (!empty($merchantAccountId)) {
            $params[PaymentDataBuilder::MERCHANT_ACCOUNT_ID] = $merchantAccountId;
        }

        return $this->braintreeAdapterFactory->create()->generate($params);
    }

    /**
     * @return string
     */
    private function getBraintreeNonceRetrieveUrl()
    {
        return $this->url->getUrl('braintree/payment/getnonce', ['_secure' => true]);
    }

    /**
     * @return string
     */
    private function getCreatePaymentIntentUrl()
    {
        return $this->url->getUrl('tnw_stripe/paymentintent/create', ['_secure' => true]);
    }

    /**
     * @return string
     */
    private function getProcessUrl()
    {
        return $this->url->getUrl('tnw_subscriptions/subscription_queue/processPost', ['_secure' => true]);
    }

    /**
     * @return string|null
     */
    private function getVaultedCardPublicHash()
    {
        if (!$this->getProfile()->getPayment()->getPaymentToken()
            || !$this->paymentTokenManagement->getByGatewayToken(
                $this->getProfile()->getPayment()->getPaymentToken(),
                $this->getPaymentMethodCode(),
                $this->getProfile()->getCustomerId()
            )
        ) {
            $profilePayment = $this->profileManager->getEngine()->getPaymentAdditionalInfo($this->getProfile());
            if (isset($profilePayment['public_hash'])) {
                return $profilePayment['public_hash'];
            } else {
                return null;
            }
        }
        return $this->paymentTokenManagement->getByGatewayToken(
            $this->getProfile()->getPayment()->getPaymentToken(),
            $this->getPaymentMethodCode(),
            $this->getProfile()->getCustomerId()
        )->getPublicHash();
    }

    /**
     * @return string
     */
    private function getPaymentMethodCode()
    {
        return str_replace(['_cc_vault', '_vault'], '', $this->getProfile()->getPayment()->getEngineCode());
    }

    /**
     * @return string
     */
    public function getGrandTotal()
    {
        return $this->priceCurrency->format(
            $this->getTempQuote()->getGrandTotal(),
            true,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getTempQuote()->getStore()
        );
    }

    /**
     * @return bool
     */
    public function isDataValid()
    {
        return $this->getRebill() && $this->getTempQuote() && $this->getProfile();
    }
}
