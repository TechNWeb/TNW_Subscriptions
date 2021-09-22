<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SetInstallmentVars set installment data accessible for email templates
 */
class SetInstallmentVars implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $transport = $observer->getTransport();
        $paidInstallment = $transport->getOrder()->getExtensionAttributes();
        if ($paidInstallment->getSubscriptionPaidInstallment() !== null) {
            $dataToSet = [
                'subscription_paid_installment',
                'subscription_final_installment_date',
                'subscription_first_installment_date',
                'subscription_expire_cc',
                'subscription_total_static_billing_cycles'
            ];

            foreach ($dataToSet as $requiredData) {
                $transport->setData(
                    $requiredData,
                    $paidInstallment->getData($requiredData)
                );
            }
        }
    }
}
