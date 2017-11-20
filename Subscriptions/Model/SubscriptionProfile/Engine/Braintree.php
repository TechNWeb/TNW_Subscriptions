<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Braintree
 */
class Braintree extends Base
{
    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return [
            'payment_token' => $payment->getAdditionalInformation(''),
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => $payment->getCcType(),
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
                OrderPaymentInterface::CC_EXP_MONTH => $payment->getCcExpMonth(),
                OrderPaymentInterface::CC_EXP_YEAR => $payment->getCcExpYear()
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getDecodedPaymentAdditionalInfo())
            ? $profile->getDecodedPaymentAdditionalInfo()
            : [];

        $result[OrderPaymentInterface::METHOD] = 'braintree';

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        return [
            'paymentMethodNonce' => ''
        ];
    }

    /**
     * @inheritdoc
     */
    public function processProfileByRequestData($requestData)
    {
        $tokenHash = '';
        //$requestData['nonce'];

        $this->getProfile()->setTokenHash($tokenHash);
        return $this;
    }
}