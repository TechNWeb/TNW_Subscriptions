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
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $quoteSession;

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

    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    /**
     * @var \Magento\Catalog\Helper\Product\ConfigurationPool
     */
    private $configurationPool;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Directory\Model\Country\Postcode\ConfigInterface $postCodesConfig,
        \Magento\Customer\Model\Url $customerUrlManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->formKey = $formKey;
        $this->customerRepository = $customerRepository;
        $this->quoteSession = $quoteSession;
        $this->customerSession = $customerSession;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->httpContext = $httpContext;
        $this->postCodesConfig = $postCodesConfig;
        $this->customerUrlManager = $customerUrlManager;
        $this->urlBuilder = $urlBuilder;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->imageHelper = $imageHelper;
        $this->configurationPool = $configurationPool;
        $this->localeFormat = $localeFormat;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
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
        $output['quotes'] = $this->getQuotes();
        $output['isCustomerLoggedIn'] = $this->isCustomerLoggedIn();
        $output['storeCode'] = $this->getStoreCode();
        $output['postCodes'] = $this->postCodesConfig->getPostCodes();
        $output['registerUrl'] = $this->getRegisterUrl();
        $output['checkoutUrl'] = $this->getCheckoutUrl();
        $output['defaultSuccessPageUrl'] = $this->getDefaultSuccessPageUrl();
        $output['pageNotFoundUrl'] = $this->pageNotFoundUrl();
        $output['forgotPasswordUrl'] = $this->getForgotPasswordUrl();

        $quoteData['priceFormat'] = $this->localeFormat
            ->getPriceFormat(null, $this->storeManager->getStore()->getCurrentCurrencyCode());
        $quoteData['basePriceFormat'] = $this->localeFormat
            ->getPriceFormat(null, $this->storeManager->getStore()->getBaseCurrencyCode());

        $output['originCountryCode'] = $this->getOriginCountryCode();
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
     * @return string
     */
    private function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * Retrieve quote data
     *
     * @return array
     */
    private function getQuotes()
    {
        $quotes = [];
        foreach ($this->quoteSession->getSubQuotes() as $subQuote) {
            $quoteData = $subQuote->toArray();
            $quoteData['is_virtual'] = $subQuote->getIsVirtual();

            if (!$subQuote->getCustomer()->getId()) {
                $quoteData['entity_id'] = $this->quoteIdMaskFactory->create()
                    ->load($subQuote->getId(), 'quote_id')
                    ->getMaskedId();
            }

            $quoteData['items'] = [];
            foreach ($subQuote->getAllVisibleItems() as $index => $quoteItem) {
                $quoteData['items'][$index] = $quoteItem->toArray();
                $quoteData['items'][$index]['options'] = $this->getFormattedOptionValue($quoteItem);
                $quoteData['items'][$index]['thumbnail'] = $this->imageHelper
                    ->init($quoteItem->getProduct(), 'product_thumbnail_image')->getUrl();
            }

            $quotes[] = $quoteData;
        }

        return $quotes;
    }

    /**
     * Retrieve formatted item options view
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return array
     */
    protected function getFormattedOptionValue($item)
    {
        $optionsData = [];
        $options = $this->configurationPool->getByProductType($item->getProductType())->getOptions($item);
        foreach ($options as $index => $optionValue) {
            /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
            $helper = $this->configurationPool->getByProductType('default');
            $params = [
                'max_length' => 55,
                'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
            ];
            $option = $helper->getFormattedOptionValue($optionValue, $params);
            $optionsData[$index] = $option;
            $optionsData[$index]['label'] = $optionValue['label'];
        }

        return $optionsData;
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

    /**
     * @return mixed
     */
    private function getOriginCountryCode()
    {
        return $this->scopeConfig->getValue(
            \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }
}
