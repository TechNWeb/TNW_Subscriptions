<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Observer, that substitutes autoshipping method to actual one on order creation
 */
class SalesModelServiceQuoteSubmitBefore implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');
        $quote = $observer->getData('quote');
        /**
         * @var $quote CartInterface
         * @var $order OrderInterface
         */
        if (!$quote->isVirtual() && $order->getShippingMethod() == 'tnwautoship_cheapest') {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->unsetData('limit_carrier');
            $shippingAddress->requestShippingRates();
            $rates = array_filter($shippingAddress->getAllShippingRates(), function ($rate) {
                return $rate->getCode() !== 'tnwautoship_cheapest' && !$rate->getErrorMessage();
            });
            usort($rates, function ($a, $b) {
                return ($a->getPrice() > $b->getPrice()) ? +1 : -1;
            });
            $cheapest = reset($rates);
            $shippingAddress->setLimitCarrier('tnwautoship');
            $order->setShippingMethod($cheapest->getCode());
            $order->setShippingDescription($cheapest->getCarrierTitle(). ' - ' . $cheapest->getMethodTitle());
        }
    }
}
