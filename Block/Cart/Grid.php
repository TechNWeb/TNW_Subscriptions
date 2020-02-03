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

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->configurationPool = $configurationPool;
        $this->imageBuilder = $imageBuilder;
        $this->descriptionCreator = $descriptionCreator;
        $this->priceCurrency = $priceCurrency;
        $this->formKey = $formKey;
        $this->quoteItemGroup = $quoteItemGroup;
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
    public function getItemDeletePostJson($item)
    {
        return json_encode([
            'action' => $this->getUrl('tnw_subscriptions/cart/delete'),
            'data' => [
                'id' => $item->getId(),
                'form_key' => $this->formKey->getFormKey()
            ]
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

    /**
     * @return array
     */
    public function groupQuoteItems()
    {
        return $this->quoteItemGroup
            ->groups($this->checkoutSession->getQuote()->getAllVisibleItems());
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $groupItems
     *
     * @return string
     */
    public function getCaption($groupItems)
    {
        return $this->quoteItemGroup->caption($groupItems);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item[] $groupItems
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function frequencyDescription($groupItems)
    {
        return $this->quoteItemGroup->frequencyDescription($groupItems);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     */
    public function getItemPrice($item)
    {
        if (!$this->quoteItemGroup->isSubscriptionItem($item)) {
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
     * @param \Magento\Quote\Model\Quote\Item[] $groupItems
     *
     * @return string
     */
    public function getSubtotal($groupItems)
    {
        if (!$this->quoteItemGroup->isSubscriptionGroup($groupItems)) {
            $subtotal = array_reduce($groupItems, function ($carry, \Magento\Quote\Model\Quote\Item $item) {
                $carry += $item->getRowTotal();
                return $carry;
            }, 0);

            return $this->priceCurrency->format(
                $subtotal,
                true,
                \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                reset($groupItems)->getStore()
            );
        }

        return $this->descriptionCreator->getDescribedPriceHtmlByGroup($groupItems);
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

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return bool
     */
    public function allowDisplaySubscribeQty($item)
    {
        $product = $item->getProduct();
        return !(bool) $product->getData('tnw_subscr_hide_qty');
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return string|null
     */
    public function getSubscribeQty($item)
    {
        return $this->allowDisplaySubscribeQty($item) ? $item->getQty() . "x" : null;
    }
}
