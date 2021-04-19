<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model\Quote\Item;

/**
 * Class Processor - plugin to add additional logic for Magento\Quote\Model\Quote\Item\Processor::prepare method
 */
class Processor
{
    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product
     */
    private $productModifier;

    /**
     * Processor constructor.
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product $productModifier
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product $productModifier
    ) {
        $this->productModifier = $productModifier;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\Processor $subject
     * @param callable $callback
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param \Magento\Framework\DataObject $request
     * @param \Magento\Catalog\Model\Product $candidate
     */
    public function aroundPrepare(
        \Magento\Quote\Model\Quote\Item\Processor $subject,
        callable $callback,
        \Magento\Quote\Model\Quote\Item $item,
        \Magento\Framework\DataObject $request,
        \Magento\Catalog\Model\Product $candidate
    ) {
        $callback($item, $request, $candidate);

        $buyRequest = $item->getBuyRequest();
        if (isset($buyRequest['subscribe_active']) && $buyRequest['subscribe_active']) {
            $this->productModifier->reset();
            $this->productModifier->setData($buyRequest->getData());
            $this->productModifier->setProduct($candidate);

            // Set initial fee
            $this->productModifier->setInitialFeeToItem($item);

            // In case of grouped product child items, we need to set custom price from their buyRequest
            $customPrice = $buyRequest->getCustomPrice();
            if (!empty($customPrice) && !$item->getCustomPrice()) {
                $item->setCustomPrice($customPrice);
                $item->setOriginalCustomPrice($customPrice);
            }
        }
    }
}
