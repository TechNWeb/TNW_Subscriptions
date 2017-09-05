<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\Mapper as AddressMapper;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\ScopeInterface;

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
     * @var AddressMapper
     */
    private $addressMapper;

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
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param CustomerUrl $customerUrl
     * @param AddressMapper $addressMapper
     * @param AddressConfig $addressConfig
     * @param HttpContext $httpContext
     * @param DataObjectProcessor $dataObjectProcessor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        CustomerUrl $customerUrl,
        AddressMapper $addressMapper,
        AddressConfig $addressConfig,
        HttpContext $httpContext,
        DataObjectProcessor $dataObjectProcessor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerUrl = $customerUrl;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->httpContext = $httpContext;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'isCustomerLoggedIn' => $this->isCustomerLoggedIn(),
            'customerData' => $this->getCustomerData(),
            'isGuestCheckoutAllowed' => $this->isGuestCheckoutAllowed(),
            'isCustomerLoginRequired' => $this->isCustomerLoginRequired(),
            'registerUrl' => $this->customerUrl->getRegisterUrl(),
            'forgotPasswordUrl' => $this->customerUrl->getForgotPasswordUrl(),
            'autocomplete' => $this->isAutocompleteEnabled()
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

    /**
     * Set additional customer address data
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    private function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(AddressConfig::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

    /**
     * Check if guest checkout allowed
     *
     * @return bool
     */
    private function isGuestCheckoutAllowed()
    {
        return $this->scopeConfig->isSetFlag(
            'checkout/options/guest_checkout',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if customer login required
     *
     * @return bool
     */
    private function isCustomerLoginRequired()
    {
        return $this->scopeConfig->isSetFlag(
            'checkout/options/customer_must_be_logged',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is autocomplete enabled for storefront
     *
     * @return string
     */
    private function isAutocompleteEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Magento\Customer\Model\Form::XML_PATH_ENABLE_AUTOCOMPLETE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        ) ? 'on' : 'off';
    }
}
