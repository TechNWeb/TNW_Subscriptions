<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Exception\PaymentException;

/**
 * Stripe Engine
 */
class Stripe extends Base
{
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const DESCRIPTION = 'description';
    const CONFIRMATION_METHOD = 'confirmation_method';
    const PAYMENT_METHOD = 'payment_method';
    const PAYMENT_METHOD_TYPES = 'payment_method_types';
    const RECEIPT_EMAIL = 'receipt_email';
    const CUSTOMER = 'customer';
    const CAPTURE_METHOD = 'capture_method';
    const SETUP_FUTURE_USAGE = 'setup_future_usage';

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    /**
     * @var bool
     */
    private $isRebill = false;

    private $adapterFactory;

    /**
     * Stripe constructor.
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
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
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
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
            $vaultPaymentAuthorization,
            $paymentTokenManagement
        );
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->adapterFactory = $objectManager->get("TNW\Stripe\Model\Adapter\StripeAdapterFactory");
        }
        $this->transactionCustomer = $transactionCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        $additionalInfo = $payment->getAdditionalInformation();
        $cardDetails = [];
        $expirationDate = [];
        if (array_key_exists('extension_attributes', $additionalInfo)) {
            $cardDetails = json_decode($additionalInfo['extension_attributes'], true);
            $expirationDate = explode('/', $cardDetails['expirationDate']);
        }
        if (!$cardDetails && !isset($additionalInfo[OrderPaymentInterface::CC_TYPE]) && !$expirationDate) {
            $cardDetails = [
                'type' => $payment->getCcType() ? : $payment->getType()
            ];
            if ($payment->getCcExpMonth() && $payment->getCcExpYear()) {
                $expirationDate = [$payment->getCcExpMonth(), $payment->getCcExpYear()];
            } else {
                $expirationDate = $payment->getData('expirationDate')
                    ? explode('/', $payment->getData('expirationDate'))
                    : ["", ""]
                ;
            }
        }
        $result = [
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => isset($additionalInfo[OrderPaymentInterface::CC_TYPE])
                    ? $additionalInfo[OrderPaymentInterface::CC_TYPE]
                    : $cardDetails['type'],
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4() ? : $payment->getData('maskedCC'),
                OrderPaymentInterface::CC_EXP_MONTH => isset($additionalInfo[OrderPaymentInterface::CC_EXP_MONTH])
                    ? $additionalInfo[OrderPaymentInterface::CC_EXP_MONTH]
                    : $expirationDate[0],
                OrderPaymentInterface::CC_EXP_YEAR => isset($additionalInfo[OrderPaymentInterface::CC_EXP_YEAR])
                    ? $additionalInfo[OrderPaymentInterface::CC_EXP_YEAR]
                    : $expirationDate[1],
                'stripe_data' => $additionalInfo
            ]
        ];
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

        if (!isset($result['stripe_data'])) {
            $result['stripe_data'] = $this->getPaymentAdditionalInfo($profile);
        }
        if (isset($result['stripe_data']['public_hash'])) {
            $result[OrderPaymentInterface::METHOD] =  $this->getPaymentMethodCode() . '_vault';
        } else {
            $result[OrderPaymentInterface::METHOD] = $this->getPaymentMethodCode();
        }
        return $result;
    }

    /**
     * @param Payment $payment
     * @param SubscriptionProfileInterface $profile
     * @return $this
     */
    public function setPaymentExtensionAttributes(Payment $payment, SubscriptionProfileInterface $profile)
    {
        $token = $this->paymentTokenManagement->getByGatewayToken(
            $profile->getPayment()->getPaymentToken(),
            $this->getPaymentMethodCode(),
            $profile->getCustomerId()
        );
        if ($token) {
            $payment->setData('public_hash', $token->getPublicHash());
        }
        $payment->setData('method', $this->getPaymentMethodCode() . '_vault');
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        $addtionalInfo = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];
        $gateWayToken = $this->paymentTokenManagement->getByGatewayToken(
            $profile->getPayment()->getPaymentToken(),
            $this->getPaymentMethodCode(),
            $profile->getCustomerId()
        );
        if (!$gateWayToken) {
            $gateWayToken = $this->paymentTokenManagement->getByGatewayToken(
                $profile->getPayment()->getPaymentToken(),
                $this->getPaymentMethodCode(),
                0
            );
            if ($gateWayToken) {
                $gateWayToken->setCustomerId($profile->getCustomerId());
                $this->paymentTokenRepository->save($gateWayToken);
            }
        }
        $publicHash = $gateWayToken ? $gateWayToken->getPublicHash() : '';
        $result = isset($addtionalInfo['stripe_data']) ? $addtionalInfo['stripe_data'] : $addtionalInfo;
        if (!$publicHash && isset($result['public_hash'])) {
            $publicHash = $result['public_hash'];
        }
        if ($this->isRebill) {
            $result = [
                'is_active_payment_token_enabler' => true,
                'public_hash' => $publicHash,
                'customer_id' => $profile->getCustomerId()
            ];
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return 'tnw_stripe';
    }

    /**
     *
     */
    public function setRebillProcessFlag()
    {
        $this->isRebill = true;
    }

    /**
     * @return string
     */
    public function getVaultPaymentCode()
    {
        return $this->getPaymentMethodCode() . '_vault';
    }

    /**
     * @param $requestData
     * @return $this|Base|EngineInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processProfileByRequestData($requestData)
    {
        if (!empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            return parent::processProfileByRequestData($requestData);
        }
        if (empty($requestData['payment'][$this->getPaymentMethodCode()]['method'])) {
            return $this;
        }
        $payment = json_decode(
            $requestData['payment'][$this->getPaymentMethodCode()]['paymentMethod'],
            true
        );
        $quote = $this->manager->getTempQuote($this->getProfile());
        $amount = '1';
        $currency = $quote->getQuoteCurrencyCode();
        $paymentId = $payment['id'];
            $stripeAdapter = $this->adapterFactory->create();
            $cs = $stripeAdapter->customer(['payment_method' => $paymentId]);
            $params = [
                self::CUSTOMER => $cs->id,
                self::AMOUNT => $this->formatPrice($amount),
                self::CURRENCY => $currency,
                self::PAYMENT_METHOD_TYPES => ['card'],
                self::CONFIRMATION_METHOD => 'manual',
                self::CAPTURE_METHOD => 'manual',
                self::SETUP_FUTURE_USAGE => 'off_session'
            ];
            $params[self::PAYMENT_METHOD] = $paymentId;
            $paymentIntent = $stripeAdapter->createPaymentIntent($params);
            $requestData['payment'][$this->getPaymentMethodCode()]['cc_token'] = $paymentIntent->id;

            return parent::processProfileByRequestData($requestData);
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
            $payment->importData(['method' => \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE]);
            $payment->setAdditionalInformation([]);
        } else {
            parent::validatePayment($quote);
        }
    }

    /**
     * @param $price
     * @return mixed
     */
    public function formatPrice($price)
    {
        $price = sprintf('%.2F', $price);

        return str_replace('.', '', $price);
    }
}
