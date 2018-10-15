<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model\Quote\Item;

class Processor
{
    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product
     */
    private $productModifier;

    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Product $productModifier
    ) {
        $this->productModifier = $productModifier;
    }

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
        }
    }
}
