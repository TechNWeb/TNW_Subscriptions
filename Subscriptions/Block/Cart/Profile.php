<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Cart;

use Magento\Framework\View\Element\Template;

class Profile extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

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

    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \TNW\Subscriptions\Model\QuoteSessionInterface $quoteSession,
        \Magento\Catalog\Helper\Product\Configuration $configuration,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->quoteSession = $quoteSession;
        $this->configuration = $configuration;
        $this->imageBuilder = $imageBuilder;
    }

    /**
     * @return \Magento\Quote\Model\Quote|null
     */
    public function getQuote()
    {
        $quote = $this->checkoutSession->getQuote();
        if (empty($quote->getAllVisibleItems())) {
            return null;
        }

        return $quote;
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
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return string
     */
    public function getItemDescription($item)
    {
        return $item->getProduct()->getData('short_description');
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
    public function getOneTimeItemConfigureUrl($item)
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
    public function getItemConfigureUrl($item)
    {
        return $this->getUrl('tnw_subscriptions/cart/configure', [
           'id' => $item->getId(),
           'product_id' => $item->getProduct()->getId()
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
        return array_values($this->quoteSession->getSubQuotes());
    }
}
