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
 * Braintree Engine
 */
class Braintree extends Base
{
    /**
     * @var \TNW\Subscriptions\Model\Payment\BraintreeAdapterFactory
     */
    private $adapterFactory;

    /**
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger $historyLogger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \TNW\Subscriptions\Model\Payment\BraintreeAdapterFactory $adapterFactory
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger $historyLogger,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \TNW\Subscriptions\Model\Payment\BraintreeAdapterFactory $adapterFactory
    ) {
        parent::__construct($config, $context, $cartManagement, $historyLogger,
            $registry, $persistor, $zeroTotalValidator);

        $this->adapterFactory = $adapterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return [
            'payment_token' => $payment->getAdditionalInformation('payment_token'),
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => $payment->getCcType(),
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
                OrderPaymentInterface::CC_EXP_MONTH => $payment->getCcExpMonth(),
                OrderPaymentInterface::CC_EXP_YEAR => $payment->getCcExpYear(),
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
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
            'payment_method_nonce' => $this->adapterFactory->create()->generateNonce($profile->getPayment()->getPaymentToken()),
        ];
    }

    /**
     * @inheritdoc
     */
    public function processProfileByRequestData($requestData)
    {
        $paymentPostData = isset($requestData['payment']) ? $requestData['payment'] : [];
        foreach ($paymentPostData as $code => $methodData) {
            if (!$methodData['method']) {
                continue;
            }

            /** @var \Braintree\CreditCard $paymentMethod */
            $paymentMethod = $this->adapterFactory->create()->generatePaymentMethod(
                $this->getProfile()->getCustomer(), $methodData['nonce']);

            $this->getProfile()->getPayment()
                ->setPaymentToken($paymentMethod->token)
                ->setEncodedPaymentAdditionalInfo([
                    OrderPaymentInterface::CC_TYPE => $methodData['additional']['cc_type'],
                    OrderPaymentInterface::CC_LAST_4 => $paymentMethod->last4,
                    OrderPaymentInterface::CC_EXP_MONTH => $paymentMethod->expirationMonth,
                    OrderPaymentInterface::CC_EXP_YEAR => $paymentMethod->expirationYear,
                ]);

            break;
        }

        return $this;
    }
}
