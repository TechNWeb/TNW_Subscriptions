<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SetInstallmentVars set installment data accessible for email templates
 */
class SetInstallmentVars implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $transport = $observer->getTransport();
        $paidInstallment = $transport->getOrder()->getExtensionAttributes();
        try {
            if ($paidInstallment->getSubscriptionPaidInstallment() !== null) {
                $transport->setData(
                    'subscription_paid_installment',
                    $paidInstallment->getSubscriptionPaidInstallment()
                );
                $transport->setData(
                    'subscription_final_installment_date',
                    $paidInstallment->getSubscriptionFinalInstallmentDate()
                );
                $transport->setData(
                    'subscription_first_installment_date',
                    $paidInstallment->getSubscriptionFirstInstallmentDate()
                );
                $transport->setData(
                    'subscription_expire_cc',
                    $paidInstallment->getSubscriptionExpireCc()
                );
                $transport->setData(
                    'subscription_total_static_billing_cycles',
                    $paidInstallment->getSubscriptionTotalStaticBillingCycles()
                );
            }
        } catch (\Throwable  $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
