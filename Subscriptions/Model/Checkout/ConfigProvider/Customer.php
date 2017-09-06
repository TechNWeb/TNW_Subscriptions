<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use Magento\Framework\UrlInterface;
use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * Customer config for Cart.
 */
class Customer implements ConfigProviderInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var AddressConfig
     */
    private $addressConfig;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param CustomerUrl $customerUrl
     * @param AddressConfig $addressConfig
     * @param HttpContext $httpContext
     * @param DataObjectProcessor $dataObjectProcessor
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        CustomerUrl $customerUrl,
        AddressConfig $addressConfig,
        HttpContext $httpContext,
        DataObjectProcessor $dataObjectProcessor,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $url
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerUrl = $customerUrl;
        $this->addressConfig = $addressConfig;
        $this->httpContext = $httpContext;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'isCustomerLoggedIn' => $this->isCustomerLoggedIn(),
            'customerData' => $this->getCustomerData(),
            'registerUrl' => $this->customerUrl->getRegisterUrl(),
            'forgotPasswordUrl' => $this->customerUrl->getForgotPasswordUrl(),
            'changeAfterLoginUrl' => $this->url->getUrl('tnw_subscriptions/session/ChangeBeforeAuthUrl'),
            'loginPostUrl' => $this->customerUrl->getLoginPostUrl()
        ];
    }

    /**
     * Check if customer logged in
     *
     * @return bool
     */
    private function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }

    /**
     * Get customer data
     *
     * @return array
     */
    private function getCustomerData()
    {
        if ($this->isCustomerLoggedIn()) {
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $customerData = $this->dataObjectProcessor->buildOutputDataArray(
                $customer,
                CustomerInterface::class
            );
            foreach ($customer->getAddresses() as $key => $address) {
                $customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
            }

            return $customerData;
        }

        return [];
    }
}
