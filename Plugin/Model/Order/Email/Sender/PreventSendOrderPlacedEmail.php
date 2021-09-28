<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class PreventSendOrderPlacedEmail preventing send place order
 * email for subscription products.
 */
class PreventSendOrderPlacedEmail
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Prevent email sending if it's subscription order && installment data
     * not calculated
     *
     * @param OrderSender $sender
     * @param $proceed
     * @param $order
     * @return false|mixed
     */
    public function aroundSend(OrderSender $sender, $proceed, $order)
    {
        $isSubscriptionOrder = false;
        foreach ($order->getItems() as $item) {
            $buyRequest = $item->getProductOptions()['info_buyRequest'];
            if (array_key_exists(
                'subscribe_active',
                $buyRequest
            ) && $buyRequest['subscribe_active'] == 1) {
                if ($order->getExtensionAttributes()->getSubscriptionPaidInstallment() == null) {
                    $isSubscriptionOrder = true;
                }
            }
        }

        if ($isSubscriptionOrder) {
            $result = false;
        } else {
            $result = $proceed($order);
        }

        return $result;
    }
}
