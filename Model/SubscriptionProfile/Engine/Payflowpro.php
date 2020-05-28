<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
     * @var \Magento\Vault\Model\PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * Payflowpro constructor.
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
    ) {
        parent::__construct(
            $config,
            $context,
            $cartManagement,
            $persistor,
            $zeroTotalValidator,
            $encryptor,
            $paymentTokenRepository,
            $manager,
            $vaultPaymentAuthorization
        );
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        $paymentToken = $payment->getAdditionalInformation(PaypalPayflow::PNREF);
        if (!$paymentToken && $payment->getAdditionalInformation('public_hash')) {
            $token = $this->paymentTokenManagement->getByPublicHash(
                $payment->getAdditionalInformation('public_hash'),
                $payment->getQuote()->getCustomerId()
            );
            if ($token) {
                $paymentToken = $token->getGatewayToken();
            }
        }
        return [
            'payment_token' => $paymentToken,
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
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
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
            PaypalPayflow::PNREF => $profile->getPayment()->getPaymentToken()
        ];
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

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return Config::METHOD_PAYFLOWPRO;
    }
}
