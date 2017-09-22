<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Paypal\Model\Config;
use Magento\Paypal\Model\Payflowpro as PaypalPayflow;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

/**
 * Class Payflowpro
 */
class Payflowpro extends Base
{
    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return [
            'payment_token' => $payment->getAdditionalInformation(PaypalPayflow::PNREF),
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

        $result[OrderPaymentInterface::METHOD] = Config::METHOD_PAYFLOWPRO;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        return [
            PaypalPayflow::PNREF => $profile->getPaymentToken()
        ];
    }

    /**
     * @inheritdoc
     */
    public function processProfileByRequestData($requestData)
    {
        $additionalData = [];
        $tokenHash = '';
        $paymentData = $this->getPersistor()->get(self::PAYMENT_DATA_KEY);
        if (isset($paymentData[SubscriptionProfileInterface::ID],
            $paymentData[SubscriptionProfileInterface::TOKEN_HASH])) {
            if ((int)$paymentData[SubscriptionProfileInterface::ID] === (int)$this->getProfile()->getId()) {
                $tokenHash = $paymentData[SubscriptionProfileInterface::TOKEN_HASH];
            }
        }
        $paymentPostData = isset($requestData['payment']) ? $requestData['payment'] :[];
        foreach ($paymentPostData as $code => $methodData) {
            if ($methodData['method']) {
                $additionalData = isset($methodData['additional']) ? $methodData['additional'] : [];

                break;
            }
        }
        $this->getProfile()->setTokenHash($tokenHash);
        $this->getProfile()->setEncodedPaymentAdditionalInfo($additionalData);
        return $this;
    }
}