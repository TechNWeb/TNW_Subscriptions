<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

class DefaultConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    private $formKey;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    private $customerSession;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    private $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    private $addressConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @var \Magento\Directory\Model\Country\Postcode\ConfigInterface
     */
    private $postCodesConfig;

    /**
     * @var \Magento\Customer\Model\Url
     */
    private $customerUrlManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Directory\Model\Country\Postcode\ConfigInterface $postCodesConfig,
        \Magento\Customer\Model\Url $customerUrlManager,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->formKey = $formKey;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->httpContext = $httpContext;
        $this->postCodesConfig = $postCodesConfig;
        $this->customerUrlManager = $customerUrlManager;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get config
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $output['formKey'] = $this->formKey->getFormKey();
        $output['customerData'] = $this->getCustomerData();
        $output['isCustomerLoggedIn'] = $this->isCustomerLoggedIn();
        $output['postCodes'] = $this->postCodesConfig->getPostCodes();
        $output['registerUrl'] = $this->getRegisterUrl();
        $output['checkoutUrl'] = $this->getCheckoutUrl();
        $output['defaultSuccessPageUrl'] = $this->getDefaultSuccessPageUrl();
        $output['pageNotFoundUrl'] = $this->pageNotFoundUrl();
        $output['forgotPasswordUrl'] = $this->getForgotPasswordUrl();

        return $output;
    }

    /**
     * Retrieve customer data
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerData()
    {
        if (!$this->isCustomerLoggedIn()) {
            return [];
        }

        $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        if (!$customer instanceof \Magento\Framework\Api\AbstractSimpleObject) {
            return [];
        }

        $customerData = $customer->__toArray();
        foreach ($customer->getAddresses() as $key => $address) {
            $customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
        }

        return $customerData;
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
            ->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     * @codeCoverageIgnore
     */
    private function isCustomerLoggedIn()
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * Retrieve customer registration URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getRegisterUrl()
    {
        return $this->customerUrlManager->getRegisterUrl();
    }

    /**
     * Retrieve checkout URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getCheckoutUrl()
    {
        return $this->urlBuilder->getUrl('checkout');
    }

    /**
     * Retrieve checkout URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function pageNotFoundUrl()
    {
        return $this->urlBuilder->getUrl('checkout/noroute');
    }

    /**
     * Retrieve default success page URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getDefaultSuccessPageUrl()
    {
        return $this->urlBuilder->getUrl('checkout/onepage/success/');
    }

    /**
     * Return forgot password URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    private function getForgotPasswordUrl()
    {
        return $this->customerUrlManager->getForgotPasswordUrl();
    }
}
