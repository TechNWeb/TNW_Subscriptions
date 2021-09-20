<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder;

/**
 * Class OrderRepositoryPlugin for set installment extension attributes
 */
class OrderRepositoryPlugin
{
    /**
     *
     * @var OrderExtensionFactory
     */
    protected $extensionFactory;

    /**
     * @var SubscriptionProfileOrder
     */
    private $subscriptionProfileOrder;

    /**
     * OrderRepositoryPlugin constructor
     *
     * @param OrderExtensionFactory $extensionFactory
     * @param SubscriptionProfileOrder $subscriptionProfileOrder
     */
    public function __construct(
        OrderExtensionFactory $extensionFactory,
        SubscriptionProfileOrder $subscriptionProfileOrder
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->subscriptionProfileOrder = $subscriptionProfileOrder;
    }

    /**
     * Add installment extension attribute to order data object to make it accessible in API data of order record
     *
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        $installmentData = $this->subscriptionProfileOrder->getInstallmentDataByOrderId($order->getEntityId());
        if (isset($installmentData['paid_installment'])) {
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
            $extensionAttributes->setSubscriptionPaidInstallment($installmentData['subscription_paid_installment']);
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

        return $order;
    }
}
