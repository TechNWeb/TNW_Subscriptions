<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Braintree\Exception;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Payment\Model\Checks\ZeroTotal;
use Magento\Paypal\Model\Config;
use Magento\Paypal\Model\Payflowpro as PaypalPayflow;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;

/**
 * Class Braintree
 */
class Braintree extends Base
{
    /**
     * Braintree constructor.
     * @param \TNW\Subscriptions\Model\Config $config
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param MessageHistoryLogger $historyLogger
     * @param Registry $registry
     * @param DataPersistorInterface $persistor
     * @param ZeroTotal $zeroTotalValidator
     * @throws Exception
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        Context $context,
        CartManagementInterface $cartManagement,
        MessageHistoryLogger $historyLogger,
        Registry $registry,
        DataPersistorInterface $persistor,
        ZeroTotal $zeroTotalValidator)
    {
        throw new Exception('Need to implement it!');
        parent::__construct($config, $context, $cartManagement, $historyLogger, $registry, $persistor, $zeroTotalValidator);
    }


    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        throw new Exception('Need to implement it!');
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
        $paymentPostData = isset($requestData['payment']) ? $requestData['payment'] : [];
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