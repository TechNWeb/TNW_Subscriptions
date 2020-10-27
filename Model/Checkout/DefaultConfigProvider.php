<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

class DefaultConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    private $formKey;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
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

    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    private $quoteItemRepository;

    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    private $quoteTotalRepository;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    /**
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface
     */
    private $shippingMethodManager;

    /**
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        \Magento\Quote\Api\CartItemRepositoryInterface $quoteItemRepository,
        \Magento\Quote\Api\CartTotalRepositoryInterface $quoteTotalRepository,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManager,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
    ) {
        $this->formKey = $formKey;
        $this->customerRepository = $customerRepository;
        $this->checkoutSession = $checkoutSession;
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
        $this->descriptionCreator = $descriptionCreator;
        $this->quoteItemRepository = $quoteItemRepository;
        $this->quoteTotalRepository = $quoteTotalRepository;
        $this->checkoutHelper = $checkoutHelper;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->paymentMethodManagement = $paymentMethodManagement;
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
        $output['quoteData'] = $this->getQuoteData();
        $output['quoteItemData'] = $this->getQuoteItemData();
        $output['quoteGroupData'] = $this->getQuoteGroupData();
        $output['isCustomerLoggedIn'] = $this->isCustomerLoggedIn();
        $output['selectedShippingMethod'] = $this->getSelectedShippingMethod();
        $output['storeCode'] = $this->getStoreCode();
        $output['isGuestCheckoutAllowed'] = $this->isGuestCheckoutAllowed();
        $output['postCodes'] = $this->postCodesConfig->getPostCodes();
        $output['registerUrl'] = $this->getRegisterUrl();
        $output['checkoutUrl'] = $this->getCheckoutUrl();
        $output['defaultSuccessPageUrl'] = $this->getDefaultSuccessPageUrl();
        $output['pageNotFoundUrl'] = $this->pageNotFoundUrl();
        $output['forgotPasswordUrl'] = $this->getForgotPasswordUrl();

        $output['priceFormat'] = $this->localeFormat
            ->getPriceFormat(null, $this->checkoutSession->getQuote()->getQuoteCurrencyCode());
        $output['basePriceFormat'] = $this->localeFormat
            ->getPriceFormat(null, $this->checkoutSession->getQuote()->getBaseCurrencyCode());

        $output['totalsData'] = $this->getTotalsData();
        $output['paymentMethods'] = $this->getPaymentMethods();

        $output['originCountryCode'] = $this->getOriginCountryCode();

        //TODO: payflow implementation
        $output['msp_recaptcha']['enabled']['paypal'] = false;
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
     * Retrieve selected shipping method
     *
     * @return array|null
     */
    private function getSelectedShippingMethod()
    {
        $shippingMethodData = null;
        try {
            $quoteId = $this->checkoutSession->getQuote()->getId();
            $shippingMethod = $this->shippingMethodManager->get($quoteId);
            if ($shippingMethod) {
                $shippingMethodData = $shippingMethod->__toArray();
            }
        } catch (\Exception $exception) {
            $shippingMethodData = null;
        }

        return $shippingMethodData;
    }

    /**
     * @return string
     */
    private function getStoreCode()
    {
        return $this->checkoutSession->getQuote()->getStore()->getCode();
    }

    /**
     * @return bool
     */
    private function isGuestCheckoutAllowed()
    {
        return $this->checkoutHelper->isAllowedGuestCheckout($this->checkoutSession->getQuote());
    }

    /**
     * @return array
     */
    private function getQuoteData()
    {
        $quoteData = [];
        if ($this->checkoutSession->getQuote()->getId()) {
            $quote = $this->checkoutSession->getQuote();

            $quoteData = $quote->toArray();
            $quoteData['is_virtual'] = $quote->getIsVirtual();

            if (!$quote->getCustomer()->getId()) {
                /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
                $quoteIdMask = $this->quoteIdMaskFactory->create();
                $quoteData['entity_id'] = $quoteIdMask
                    ->load($this->checkoutSession->getQuote()->getId(), 'quote_id')
                    ->getMaskedId();
            }
        }

        return $quoteData;
    }

    private function getQuoteItemData()
    {
        $quoteItemData = [];
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $quoteItems = $this->quoteItemRepository->getList($quoteId);
            foreach ($quoteItems as $index => $quoteItem) {
                $quoteItemData[$index] = $quoteItem->toArray();
                $quoteItemData[$index]['options'] = $this->getFormattedOptionValue($quoteItem);

                $imageHelper = $this->imageHelper->init(
                    $this->getItemPurchaseProduct($quoteItem),
                    'product_thumbnail_image',
                    ['width' => 60, 'height' => 60]
                );

                $quoteItemData[$index]['thumbnail'] = [
                    'src' => $imageHelper->getUrl(),
                    'alt' => $imageHelper->getLabel(),
                    'width' => $imageHelper->getWidth(),
                    'height' => $imageHelper->getHeight(),
                ];

                $quoteItemData[$index]['tnw_subscr_unlock_preset_qty'] = $quoteItem->getProduct()->getData(
                    'tnw_subscr_unlock_preset_qty'
                );
                $quoteItemData[$index]['tnw_subscr_hide_qty'] = $quoteItem->getProduct()->getData(
                    'tnw_subscr_hide_qty'
                );
            }
        }

        return $quoteItemData;
    }

    private function getQuoteGroupData()
    {
        $quoteGroupData = [];
        $quoteId = $this->checkoutSession->getQuote()->getId();
        if ($quoteId) {
            $quoteItems = $this->quoteItemRepository->getList($quoteId);
            foreach ($this->quoteItemGroup->groups($quoteItems) as $frequency_id => $group) {
                $quoteGroupData[] = [
                    'caption' => $this->quoteItemGroup->caption($group, $frequency_id),
                    'itemIds' => array_map(function ($item) {
                        return $item->getId();
                    }, $group)
                ];
            }
        }

        return $quoteGroupData;
    }

    /**
     * Get item configurable child product
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return \Magento\Catalog\Model\Product
     */
    private function getItemPurchaseProduct($item)
    {
        if ($option = $item->getOptionByCode('simple_product')) {
            return $option->getProduct();
        }

        if ($option = $item->getOptionByCode('product_type')) {
            return $option->getProduct();
        }

        return $item->getProduct();
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
        return $this->urlBuilder->getUrl('tnw_subscriptions/checkout');
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
        return $this->urlBuilder->getUrl('tnw_subscriptions/checkout/success');
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

    /**
     * Return quote totals data
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTotalsData()
    {
        /** @var \Magento\Quote\Model\Cart\Totals $totals */
        $totals = $this->quoteTotalRepository->get($this->checkoutSession->getQuote()->getId());

        $items = [];
        /** @var  \Magento\Quote\Model\Cart\Totals\Item $item */
        foreach ($totals->getItems() as $item) {
            $items[] = $item->__toArray();
        }

        $totalSegmentsData = [];
        /** @var \Magento\Quote\Model\Cart\TotalSegment $totalSegment */
        foreach ($totals->getTotalSegments() as $totalSegment) {
            $totalSegmentArray = $totalSegment->toArray();
            if (\is_object($totalSegment->getExtensionAttributes())) {
                $totalSegmentArray['extension_attributes'] = $totalSegment->getExtensionAttributes()->__toArray();
            }

            $totalSegmentsData[] = $totalSegmentArray;
        }

        $totals->setItems($items);
        $totals->setTotalSegments($totalSegmentsData);
        $totalsArray = $totals->toArray();

        if (\is_object($totals->getExtensionAttributes())) {
            $totalsArray['extension_attributes'] = $totals->getExtensionAttributes()->__toArray();
        }

        return $totalsArray;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getPaymentMethods()
    {
        $paymentMethods = [];
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getIsVirtual()) {
            foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
                $paymentMethods[] = [
                    'code' => $paymentMethod->getCode(),
                    'title' => $paymentMethod->getTitle()
                ];
            }
        }

        return $paymentMethods;
    }
}
