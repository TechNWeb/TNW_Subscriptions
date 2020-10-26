<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\OfflinePayments\Model\Cashondelivery as CashondeliveryPayment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Cashondelivery - engine to process cash on delivery payments
 */
class Cashondelivery extends Base
{
    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        return [
            OrderPaymentInterface::METHOD => CashondeliveryPayment::PAYMENT_METHOD_CASHONDELIVERY_CODE
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
