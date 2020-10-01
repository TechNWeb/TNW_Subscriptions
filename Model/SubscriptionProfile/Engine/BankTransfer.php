<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\OfflinePayments\Model\Banktransfer as BanktransferPayment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class BankTransfer - engine to process bank transfer payments
 */
class BankTransfer extends Base
{
    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        return [
            OrderPaymentInterface::METHOD => BanktransferPayment::PAYMENT_METHOD_BANKTRANSFER_CODE
        ];
    }

    /**
     * @inheritdoc
     */
    public function processProfileByRequestData($requestData)
    {
        $this->getProfile()->getPayment()->setPaymentAdditionalInfo('');
        $this->getProfile()->getPayment()->setTokenHash('');
        return $this;
    }
}
