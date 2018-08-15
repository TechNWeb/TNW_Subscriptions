<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Cart;

use Magento\Framework\View\Element\Template;

class Profile extends Template
{
    /**
     * @var \TNW\Subscriptions\Model\QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    private $configuration;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    private $imageBuilder;

    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    private $formKey;

    public function __construct(
        Template\Context $context,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession,
        \Magento\Catalog\Helper\Product\Configuration $configuration,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteSession = $quoteSession;
        $this->configuration = $configuration;
        $this->imageBuilder = $imageBuilder;
        $this->descriptionCreator = $descriptionCreator;
        $this->priceCurrency = $priceCurrency;
        $this->formKey = $formKey;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return array
     */
    public function getItemOptions($item)
    {
        return $this->configuration->getOptions($item);
    }

    /**
     * Get item configurable child product
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getItemPurchaseProduct($item)
    {
        if ($option = $item->getOptionByCode('simple_product')) {
            return $option->getProduct();
        }

        $option = $item->getOptionByCode('product_type');
        if ($option) {
            return $option->getProduct();
        }

        return $item->getProduct();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return string
     */
    public function getItemDescription($item)
    {
        return $this->getItemPurchaseProduct($item)->getData('short_description');
    }

    /**
     * @param $product
     * @param $imageId
     * @param array $attributes
     *
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getItemImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return string
     */
    public function getItemName($item)
    {
        return $item->getProduct()->getName();
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return string
     */
    public function getItemConfigureUrl($item)
    {
        if (!$this->quoteSession->isSubscription($item->getQuoteId())) {
            return $this->getUrl('checkout/cart/configure', [
                'id' => $item->getId(),
                'product_id' => $item->getProduct()->getId()
            ]);
        }

        return $this->getUrl('tnw_subscriptions/cart/configure', [
           'id' => $item->getId(),
           'product_id' => $item->getProduct()->getId()
        ]);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return string
     */
    public function getItemDeleteUrl($item)
    {
        if (!$this->quoteSession->isSubscription($item->getQuoteId())) {
            return $this->getUrl('checkout/cart/delete', [
                'id' => $item->getId(),
                'form_key' => $this->formKey->getFormKey()
            ]);
        }

        return $this->getUrl('tnw_subscriptions/cart/delete', [
            'id' => $item->getId(),
            'form_key' => $this->formKey->getFormKey()
        ]);
    }

    /**
     * Retrieve URL to item Product
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return string
     */
    public function getItemProductUrl($item)
    {
        if ($item->getRedirectUrl()) {
            return $item->getRedirectUrl();
        }

        $product = $item->getProduct();
        $option = $item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * @param $optionValue
     *
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        $params = [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
        ];
        return $this->configuration->getFormattedOptionValue($optionValue, $params);
    }

    /**
     * @return \Magento\Quote\Model\Quote[]
     */
    public function getSubQuotes()
    {
        return $this->quoteSession->getSubQuotes();
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return string
     */
    public function getCaption($quote)
    {
        if (!$this->quoteSession->isSubscription($quote)) {
            return __('One-Time Purchase');
        }

        static $quoteIndex = 0;
        return __('Subscription Profile #%1', ++$quoteIndex);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function frequencyDescription($quote)
    {
        if (!$this->quoteSession->isSubscription($quote)) {
            return '';
        }

        $fullSubscriptionData = null;

        $initialFee = $price = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            if (!$fullSubscriptionData) {
                $fullSubscriptionData = $item->getBuyRequest()
                    ->getDataByPath('subscription_data');
            }

            $price += $item->getBuyRequest()->getDataByPath('subscription_data/non_unique/price') * $item->getQty();
            $initialFee += $this->getInitialFeeFromItem($item);
        }

        $fullSubscriptionData['non_unique']['price'] = $price;
        $fullSubscriptionData['non_unique']['totalPrice'] = $quote->getSubtotal() + $initialFee;
        $fullSubscriptionData['non_unique']['initialFee'] = $initialFee > 0;
        $fullSubscriptionData['non_unique']['isVirtual'] = $quote->isVirtual();

        return $this->descriptionCreator->getDescription($fullSubscriptionData);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function getItemPrice($item)
    {
        if (!$this->quoteSession->isSubscription($item->getQuoteId())) {
            return $this->priceCurrency->format(
                $item->getRowTotal(),
                true,
                \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                $item->getStore()
            );
        }

        return $this->descriptionCreator
            ->getDescribedItemPriceHtml(
                $item->getRowTotal(),
                $item->getBuyRequest()->getDataByPath('subscription_data'),
                $this->getInitialFeeFromItem($item)
            );
    }

    /**
     * Returns initial fee from item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return int
     */
    private function getInitialFeeFromItem($item)
    {
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
        }

        return !empty($initialFee) ? $initialFee : 0;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return bool
     */
    public function allowEditSubscribeQty($item)
    {
        $product = $item->getProduct();
        return !(bool)$product->getData('tnw_subscr_unlock_preset_qty');
    }
}
