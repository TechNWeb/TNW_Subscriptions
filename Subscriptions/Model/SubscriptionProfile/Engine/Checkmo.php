<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\OfflinePayments\Model\Checkmo as CheckmoPayment;
use Magento\Sales\Api\Data\OrderPaymentInterface;

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
}