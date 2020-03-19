<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use CyberSource\Core\Model\Config as ConfigProvider;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Exception\PaymentException;

class CyberSource extends Base
{
    private $transferFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \Magento\Payment\Gateway\Http\TransferFactoryInterface $transferFactory,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
        \TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Gateway\Config\Config $cyberSourceConfig
    ) {
        parent::__construct(
            $config,
            $context,
            $cartManagement,
            $persistor,
            $zeroTotalValidator
        );
        $cyberSourceConfig->reBillProcess();
        $this->transferFactory = $transferFactory;
        $this->transactionCustomer = $transactionCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return [
            'token_hash' => $payment->getAdditionalInformation('cybersourse_token'),
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => $payment->getCcType(),
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
                OrderPaymentInterface::CC_EXP_MONTH => $payment->getCcExpMonth(),
                OrderPaymentInterface::CC_EXP_YEAR => $payment->getCcExpYear(),
                'cybersource_data' => $payment->getAdditionalInformation()
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

        $result[OrderPaymentInterface::METHOD] = ConfigProvider::CODE;

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];
        return array_merge(
            $result['cybersource_data'],
            [OrderPaymentInterface::METHOD => ConfigProvider::CODE]
        );
    }

    public function processProfileByRequestData($requestData)
    {
        if (empty($requestData['payment'][ConfigProvider::CODE]['method'])) {
            return $this;
        }

        $customer = $this->getProfile()->getCustomer();
        if (!$customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            return $this;
        }

        /** @var string[] $additionalData */
        $additionalData = $requestData['payment'][ConfigProvider::CODE]['additional'];

        $transfer = $this->transferFactory->create([
            'firstName' => $customer->getFirstname(),
            'lastName' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'paymentMethodNonce' => $requestData['payment'][ConfigProvider::CODE]['nonce']
        ]);

        $response = $this->transactionCustomer->placeRequest($transfer);
        if ($response['object'] instanceof \Braintree\Result\Error) {
            $errors = [];
            foreach($response->errors->deepAll() AS $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new PaymentException(__('CyberSource message: %1', implode(', ', $errors)));
        }

        /** @var \Braintree\CreditCard $paymentMethod */
        $paymentMethod = $response['object']->customer->paymentMethods[0];

        $this->getProfile()->getPayment()
            ->setPaymentToken($paymentMethod->token)
            ->setEncodedPaymentAdditionalInfo([
                OrderPaymentInterface::CC_TYPE => $additionalData['cc_type'],
                OrderPaymentInterface::CC_LAST_4 => $paymentMethod->last4,
                OrderPaymentInterface::CC_EXP_MONTH => $paymentMethod->expirationMonth,
                OrderPaymentInterface::CC_EXP_YEAR => $paymentMethod->expirationYear,
            ]);

        return $this;
    }
}
