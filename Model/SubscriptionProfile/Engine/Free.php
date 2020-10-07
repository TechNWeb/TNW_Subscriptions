<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class Free - used to process the free trial payments
 */
class Free extends Base
{
    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        return [
            OrderPaymentInterface::METHOD => \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE
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
