<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\OfflinePayments\Model\Checkmo as CheckmoPayment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Checkmo - processor for checkmo payments
 */
class Checkmo extends Base
{
    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        return [
            OrderPaymentInterface::METHOD => CheckmoPayment::PAYMENT_METHOD_CHECKMO_CODE
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
