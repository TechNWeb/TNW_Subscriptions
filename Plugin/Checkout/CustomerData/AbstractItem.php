<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\CustomerData;

class AbstractItem
{
    /**
     * @var \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator
     */
    private $descriptionCreator;

    public function __construct(
        \TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator $descriptionCreator
    ) {
        $this->descriptionCreator = $descriptionCreator;
    }

    /**
     * @param \Magento\Checkout\CustomerData\AbstractItem $subject
     * @param callable $callback
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     * @throws \Zend_Json_Exception
     */
    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        callable $callback,
        \Magento\Quote\Model\Quote\Item $quoteItem
    ) {
        $subscription = $quoteItem->getOptionByCode('subscription');
        $subscriptionItemPrice = '';
        if (null !== $subscription) {
            $subscription = \Zend_Json::decode($subscription->getValue());
            $subscriptionItemPrice = $this->getSubscriptionItemPrice($quoteItem);
        }

        return \array_merge(
            $callback($quoteItem),
            ['subscription' => $subscription],
            ['subscription_price' => $subscriptionItemPrice]
        );
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return string
     * @throws \Zend_Json_Exception
     */
    private function getSubscriptionItemPrice($item)
    {
        return $this->descriptionCreator->getDescribedItemPriceHtmlByQuoteItem($item);
    }
}
