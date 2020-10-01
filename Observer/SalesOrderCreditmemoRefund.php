<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesOrderCreditmemoRefund - observer
 */
class SalesOrderCreditmemoRefund implements ObserverInterface
{
    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $observer->getData('creditmemo');
        $order = $creditmemo->getOrder();

        /** @var \Magento\Sales\Api\Data\OrderExtensionInterface $orderExtension */
        $orderExtension = $order->getExtensionAttributes();
        /** @var \Magento\Sales\Api\Data\CreditmemoExtensionInterface $creditmemoExtension */
        $creditmemoExtension = $creditmemo->getExtensionAttributes();

        $refunded = $orderExtension->getSubscriptionInitialFeeRefunded()
            + $creditmemoExtension->getSubscriptionInitialFee();
        $orderExtension->setSubscriptionInitialFeeRefunded($refunded);

        $baseRefunded = $orderExtension->getBaseSubscriptionInitialFeeRefunded()
            + $creditmemoExtension->getBaseSubscriptionInitialFee();
        $orderExtension->setBaseSubscriptionInitialFeeRefunded($baseRefunded);
    }
}
