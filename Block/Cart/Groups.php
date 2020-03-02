<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Cart;

class Groups implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup
    ) {
        $this->descriptionCreator = $descriptionCreator;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGroupQuoteItems()
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
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     *
     * @return bool
     */
    public function allowDisplaySubscribeQty($item)
    {
        $product = $item->getProduct();
        return !(bool) $product->getData('tnw_subscr_hide_qty');
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
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
     * @return string
     */
    public function getSubscriptionItemPrice($item)
    {
        return $this->descriptionCreator->getDescribedItemPriceHtmlByQuoteItem($item);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     */
    public function isSubscriptionItem($item)
    {
        return $this->quoteItemGroup->isSubscriptionItem($item);
    }
}
