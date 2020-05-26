<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Braintree\Model\Ui\ConfigProvider;
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
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * Braintree constructor.
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \Magento\Braintree\Gateway\Http\TransferFactory $transferFactory
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \Magento\Braintree\Gateway\Http\TransferFactory $transferFactory,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
    ) {
        parent::__construct(
            $config,
            $context,
            $cartManagement,
            $persistor,
            $zeroTotalValidator
        );
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->transferFactory = $transferFactory;
        $this->transactionCustomer = $transactionCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        $result = [
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => $payment->getCcType(),
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
                OrderPaymentInterface::CC_EXP_MONTH => $payment->getCcExpMonth(),
                OrderPaymentInterface::CC_EXP_YEAR => $payment->getCcExpYear(),
            ]
        ];
        $token = $payment->getAdditionalInformation('token_hash');
        if (!$token && $payment->getAdditionalInformation('public_hash')) {
            $vaultToken = $this->paymentTokenManagement
                ->getByPublicHash(
                    $payment->getAdditionalInformation('public_hash'),
                    $payment->getAdditionalInformation('customer_id')
                );
            if ($vaultToken) {
                $token = $vaultToken->getGatewayToken();
                $result['payment_token'] = $token;
            }
        } else {
            $result['token_hash'] = $token;
        }
        return $result;
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

            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
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

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validatePayment(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getBaseGrandTotal() < 0.0001) {
            /** @var Payment $payment */
            $payment = $quote->getPayment();
            $payment->unsetData('method_instance');
            $quote->setSubscriptionPaymentDataSet(true);
            $payment->importData(['method' => \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE]);
            $payment->setAdditionalInformation([]);
        } else {
            parent::validatePayment($quote);
        }
    }
}
