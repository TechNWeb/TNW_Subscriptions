<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\CustomerData;

use Magento\Framework\Serialize\SerializerInterface;
use TNW\Subscriptions\Model\ProductBillingFrequency\DescriptionCreator;

class AbstractItem
{
    /**
     * @var DescriptionCreator
     */
    private $descriptionCreator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * AbstractItem constructor.
     * @param DescriptionCreator $descriptionCreator
     * @param SerializerInterface $serializer
     */
    public function __construct(
        DescriptionCreator $descriptionCreator,
        SerializerInterface $serializer
    ) {
        $this->descriptionCreator = $descriptionCreator;
        $this->serializer = $serializer;
    }

    /**
     * @param \Magento\Checkout\CustomerData\AbstractItem $subject
     * @param callable $callback
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        callable $callback,
        \Magento\Quote\Model\Quote\Item $quoteItem
    ) {
        $subscription = $quoteItem->getOptionByCode('subscription');
        $subscriptionItemPrice = '';
        if (null !== $subscription) {
            $subscription = $this->serializer->unserialize($subscription->getValue());
            $subscriptionItemPrice = $this->getSubscriptionItemPrice($quoteItem);
        }

        return \array_merge(
            $callback($quoteItem),
            ['subscription' => $subscription],
            ['subscription_price' => $subscriptionItemPrice]
        );
    }

    /**
     * @param $item
     * @return string
     */
    private function getSubscriptionItemPrice($item)
    {
        return $this->descriptionCreator->getDescribedItemPriceHtmlByQuoteItem($item);
    }
}
