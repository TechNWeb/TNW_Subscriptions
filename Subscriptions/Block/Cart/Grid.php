<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Cart;

use Magento\Framework\View\Element\Template;

class Grid extends Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Catalog\Helper\Product\ConfigurationPool
     */
    private $configurationPool;

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
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->configurationPool = $configurationPool;
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
        return $this->configurationPool->getByProductType($item->getProductType())->getOptions($item);
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
        return $this->getUrl('checkout/cart/configure', [
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
        return $this->getUrl('checkout/cart/delete', [
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
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->configurationPool->getByProductType('default');
        $params = [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
        ];

        return $helper->getFormattedOptionValue($optionValue, $params);
    }

    public function groupQuoteItems()
    {
        return [$this->checkoutSession->getQuote()->getAllVisibleItems()];
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return string
     */
    public function getCaption($quote)
    {
        static $quoteIndex = [];

        if (true) {
            return __('One-Time Purchase');
        }

        if (!isset($quoteIndex[$quote->getId()])) {
            $quoteIndex[$quote->getId()] = \count($quoteIndex) + 1;
        }

        return __('Subscription Profile #%1', $quoteIndex[$quote->getId()]);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function frequencyDescription($quote)
    {
        if (true) {
            return '';
        }

        return $this->descriptionCreator->getDescriptionByQuote($quote);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function getItemPrice($item)
    {
        if (true) {
            return $this->priceCurrency->format(
                $item->getRowTotal(),
                true,
                \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                $item->getStore()
            );
        }

        return $this->descriptionCreator->getDescribedItemPriceHtmlByQuoteItem($item);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return string
     */
    public function getSubtotal($quote)
    {
        if (true) {
            return $this->priceCurrency->format(
                $quote->getSubtotal(),
                true,
                \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                $quote->getStore()
            );
        }

        return $this->descriptionCreator->getDescribedPriceHtmlByQuote($quote);
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
