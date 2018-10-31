<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer\QuoteSubmitSuccess;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RemoveItems implements ObserverInterface
{
    /**
     * @var bool
     */
    private $isSubscription;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * RemoveItems constructor.
     *
     * @param \Magento\Checkout\Model\Cart $cart
     * @param bool $isSubscription
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        $isSubscription
    ) {
        $this->isSubscription = $isSubscription;
        $this->cart = $cart;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getData('quote');
        if (!$quote instanceof \Magento\Quote\Model\Quote ||
            (bool)$quote->getData('is_tnw_subscription') !== $this->isSubscription
        ) {
            return;
        }

        $quoteRemove = $this->cart->getQuote();
        if (!$quoteRemove instanceof \Magento\Quote\Model\Quote ||
            (bool)$quoteRemove->getData('is_tnw_subscription') === $this->isSubscription
        ) {
            return;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            foreach ($quoteRemove->getAllVisibleItems() as $itemRemove) {
                if (!$itemRemove->compare($item)) {
                    continue;
                }

                $this->cart->removeItem($itemRemove->getItemId());
            }
        }

        $this->cart->save();
    }
}
