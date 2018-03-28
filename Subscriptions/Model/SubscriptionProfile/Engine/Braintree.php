<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Exception\PaymentException;

/**
 * Braintree Engine
 */
class Braintree extends Base
{
    /**
     * @var \Magento\Braintree\Gateway\Http\TransferFactory
     */
    private $transferFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger $historyLogger
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \Magento\Braintree\Gateway\Http\TransferFactory $transferFactory
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger $historyLogger,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \Magento\Braintree\Gateway\Http\TransferFactory $transferFactory,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        parent::__construct(
            $config,
            $context,
            $cartManagement,
            $historyLogger,
            $registry,
            $persistor,
            $zeroTotalValidator
        );

        $this->transferFactory = $transferFactory;
        $this->transactionCustomer = $transactionCustomer;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return [
            'token_hash' => $payment->getAdditionalInformation('token_hash'),
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
            'token_hash' => $profile->getPayment()->getTokenHash(),
        ];
    }

    /**
     * @inheritdoc
     * @param $requestData
     * @return Braintree
     * @throws PaymentException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function processProfileByRequestData($requestData)
    {
        $paymentPostData = isset($requestData['payment']) ? $requestData['payment'] : [];
        foreach ($paymentPostData as $code => $methodData) {
            if (!$methodData['method']) {
                continue;
            }

            $customer = $this->getProfile()->getCustomer();
            $transfer = $this->transferFactory->create([
                'firstName' => $customer->getFirstname(),
                'lastName' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'paymentMethodNonce' => $methodData['nonce']
            ]);

            $response = $this->transactionCustomer->placeRequest($transfer);
            if ($response['object'] instanceof \Braintree\Result\Error) {
                $errors = [];
                foreach($response->errors->deepAll() AS $error) {
                    $errors[] = "{$error->code}: {$error->message}";
                }

                throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
            }

            /** @var \Braintree\CreditCard $paymentMethod */
            $paymentMethod = $response['object']->customer->paymentMethods[0];

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
