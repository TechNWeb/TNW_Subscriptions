<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder;

/**
 * Class PreventSendOrderPlacedEmail preventing send place order
 * email for subscription products.
 */
class PreventSendOrderPlacedEmail
{
    /**
     * @var SubscriptionProfileOrder
     */
    private $subscriptionProfileOrder;

    /**
     * PreventSendOrderPlacedEmail constructor.
     * @param SubscriptionProfileOrder $subscriptionProfileOrder
     */
    public function __construct(SubscriptionProfileOrder $subscriptionProfileOrder)
    {
        $this->subscriptionProfileOrder = $subscriptionProfileOrder;
    }

    /**
     * Prevent email sending if it's subscription order && installment data
     * not calculated
     *
     * @param OrderSender $sender
     * @param $proceed
     * @param $order
     * @param bool $forceSyncMode
     * @return bool
     */
    public function aroundSend(OrderSender $sender, $proceed, $order, $forceSyncMode = false)
    {
        $isSubscriptionOrder = false;
        foreach ($order->getItems() as $item) {
            $buyRequest = $item->getProductOptions()['info_buyRequest'];
            if (array_key_exists(
                'subscribe_active',
                $buyRequest
            ) && $buyRequest['subscribe_active'] == 1) {
                if ($order->getExtensionAttributes()->getSubscriptionPaidInstallment() == null) {
                    $extensionAttributes = $order->getExtensionAttributes();
                    $installmentData = $this->subscriptionProfileOrder
                        ->getInstallmentDataByOrderId($order->getEntityId());
                    if (!$installmentData
                        || (is_array($installmentData)
                            && array_key_exists('subscription_paid_installment', $installmentData)
                            && !$installmentData['subscription_paid_installment'])
                    ) {
                        $isSubscriptionOrder = true;
                    } elseif ($installmentData && is_array($installmentData)) {
                        $extensionAttributes->setSubscriptionPaidInstallment(
                            $installmentData['subscription_paid_installment']
                        );
                        $extensionAttributes->setSubscriptionFinalInstallmentDate(
                            $installmentData['subscription_final_installment_date']
                        );
                        $extensionAttributes->setSubscriptionFirstInstallmentDate(
                            $installmentData['subscription_first_installment_date']
                        );
                        $extensionAttributes->setSubscriptionExpireCc(
                            $installmentData['subscription_expire_cc']
                        );
                        $extensionAttributes->setSubscriptionTotalStaticBillingCycles(
                            $installmentData['subscription_total_static_billing_cycles']
                        );
                        $order->setExtensionAttributes($extensionAttributes);
                    }
                }
            }
        }

        if ($isSubscriptionOrder) {
            $result = false;
        } else {
            $result = $proceed($order, $forceSyncMode);
        }

        return $result;
    }
}
