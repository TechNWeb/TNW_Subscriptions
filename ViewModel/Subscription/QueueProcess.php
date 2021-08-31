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
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder;
use TNW\Subscriptions\Api\Data\ReBillInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Api\UrlBuilderInterface as SubscriptionUrlBuilderInterface;
use TNW\Subscriptions\Model\Payment\Braintree\AdapterFactory;
use TNW\Subscriptions\Model\Payment\DataBuilder;
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
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var DataBuilder
     */
    private $dataBuilder;

    /**
     * @var mixed
     */
    private $braintreeConfig;
    /**
     * @var AdapterFactory
     */
    private $braintreeAdapterFactory;
    /**
     * @var UrlInterface
     */
    private $url;

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
     * @param DataBuilder $dataBuilder
     * @param AdapterFactory $braintreeAdapterFactory
     * @param ObjectManagerInterface $objectManager
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
        DataBuilder $dataBuilder,
        AdapterFactory $braintreeAdapterFactory,
        ObjectManagerInterface $objectManager
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
        $this->moduleManager = $moduleManager;
        $this->dataBuilder = $dataBuilder;
        $this->braintreeAdapterFactory = $braintreeAdapterFactory;
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->braintreeConfig = $objectManager->get(\PayPal\Braintree\Gateway\Config\Config::class);
        }
        $this->url = $url;
    }

    /**
     * Get token from request
     * @return string
     */
    public function getToken()
    {
        return $this->request->getParam('token');
    }

    /**
     * Get Json config for queue-process component
     * @return bool|string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getJsConfig()
    {
        if ($this->getProfile()->getPayment()->getEngineCode() === 'braintree_cc_vault') {
            return $this->serializer->serialize(
                [
                    'component' => 'TNW_Subscriptions/js/components/rebill/queue-process-braintree',
                    'config' => [
                        'nonceUrl' => $this->getNonceRetrieveUrl(),
                        'clientToken' => $this->getBraintreeClientToken(),
                        'three_d_enabled' => $this->braintreeConfig->isVerify3DSecure(),
                        'thresholdAmount' => $this->braintreeConfig->getThresholdAmount(),
                        'totalAmount' => $this->getTempQuote()->getGrandTotal(),
                        'specificCountries' => $this->braintreeConfig->get3DSecureSpecificCountries(),
                        'useCvvVault' => $this->braintreeConfig->isCvvEnabledVault(),
                    ]
                ]
            );
        }
        return '{}';
    }

    /**
     * Get rebill object
     * @return ReBillInterface|bool
     */
    public function getRebill()
    {
        if (!$this->rebill) {
            try {
                $this->rebill = $this->reBillRepository->getByToken($this->getToken());
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return $this->rebill;
    }

    /**
     * Get profile links by ids
     * @return string
     */
    public function getProfileLinks()
    {
        $ids = $this->getRebill()->getSubscriptionProfiles();
        $result = [];
        foreach ($ids as $id) {
            $result[] = $this->urlBuilder->getEditHtmlLink($id, true);
        }
        return implode(', ', $result);
    }

    /**
     * @return Quote
     */
    private function getTempQuote()
    {
        if (!$this->tempQuote) {
            $this->tempQuote = $this->profileManager->getTempQuoteByProfileIds(
                $this->getRebill()->getSubscriptionProfiles()
            );
        }
        return $this->tempQuote;
    }

    /**
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
     * @return SubscriptionProfileInterface
     * @throws NoSuchEntityException
     */
    public function getProfile()
    {
        if (!$this->profile) {
            $this->profile = $this->profileRepository->getById($this->getRebill()->getSubscriptionProfiles()[0]);
        }
        return $this->profile;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPaymentDetails()
    {
        return $this->getProfile()->getPayment()->getDecodedPaymentAdditionalInfo();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
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
    private function getNonceRetrieveUrl()
    {
        return $this->url->getUrl('braintree/payment/getnonce', ['_secure' => true]);
    }
}
