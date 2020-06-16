<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Cart;

use Magento\Checkout\Model\Session;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ProductTypeManagerResolver;
use TNW\Subscriptions\Model\Quote\ItemGroup;

class Groups implements \Magento\Framework\View\Element\Block\ArgumentInterface
{

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var ItemGroup
     */
    private $quoteItemGroup;
    /**
     * @var ProductTypeManagerResolver
     */
    private $productTypeResolver;

    /**
     * Groups constructor.
     * @param Session $checkoutSession
     * @param DescriptionCreator $descriptionCreator
     * @param ItemGroup $quoteItemGroup
     * @param ProductTypeManagerResolver $productTypeResolver
     */
    public function __construct(
        Session $checkoutSession,
        DescriptionCreator $descriptionCreator,
        ItemGroup $quoteItemGroup,
        ProductTypeManagerResolver $productTypeResolver
    ) {
        $this->descriptionCreator = $descriptionCreator;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->checkoutSession = $checkoutSession;
        $this->productTypeResolver = $productTypeResolver;
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
    public function getCaption($frequency_id, $groupItems)
    {
        return $this->quoteItemGroup->caption($frequency_id, $groupItems);
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
        return !(bool) $this->getProductDataObject($item)->getData(Attribute::SUBSCRIPTION_HIDE_QTY);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     *
     * @return bool
     */
    public function allowEditSubscribeQty($item)
    {
        return !(bool)$this->getProductDataObject($item)->getData(Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Magento\Framework\DataObject
     */
    public function getProductDataObject($item)
    {
        $children = $item->getChildren();
        $child = is_array($children) ? reset($children) : null;
        $arguments = !empty($child) ? ['child_product' => $child->getProduct()] : [];
        return $this->productTypeResolver->resolve($item->getProduct()->getTypeId())
            ->getProductDataObject($item->getProduct(), $arguments);
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
