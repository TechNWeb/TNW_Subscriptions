<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\CustomerData;

class AbstractItem
{
    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        callable $callback,
        \Magento\Quote\Model\Quote\Item $quoteItem
    ) {
        $subscription = $quoteItem->getOptionByCode('subscription');
        if (null !== $subscription) {
            $subscription = \Zend_Json::decode($subscription->getValue());
        }

        return \array_merge($callback($quoteItem), ['subscription' => $subscription]);
    }
}
