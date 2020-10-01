<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Braintree Engine - processor for braintree payments
 */
class Braintree extends Base
{
    /**
     * Payment method code
     */
    const CODE = 'braintree';

    /**
     * Braintree nonce field name
     */
    const PAYMENT_METHOD_NONCE = 'payment_method_nonce';

    /**
     * @var mixed
     */
    private $transferFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    /**
     * @var mixed
     */
    private $command;

    /**
     * Braintree constructor.
     * @param \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement,
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
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
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->command = $objectManager->get(\PayPal\Braintree\Gateway\Command\GetPaymentNonceCommand::class);
            $this->transferFactory = $objectManager->get(\PayPal\Braintree\Gateway\Http\TransferFactory::class);
        }
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

        $result[OrderPaymentInterface::METHOD] = self::CODE;

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
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return self::CODE;
    }

    /**
     * @param $requestData
     * @return $this|Base|EngineInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processProfileByRequestData($requestData)
    {
        if (empty($requestData['payment'][self::CODE]['method'])
            && empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            return $this;
        }

        if (empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            $paymentMethodNonce = $requestData['payment'][self::CODE]['nonce'];
            $paymentCode = self::CODE;
        } else {
            $subject = [
                'public_hash' => $requestData['payment'][$this->getVaultPaymentCode()]['additional']['publicHash'],
                'customer_id' => $this->getProfile()->getCustomerId(),
                'store_id' => $this->getProfile()->getCustomer()->getStoreId(),
            ];
            try {
                $result = $this->command->execute($subject)->get();
                $paymentMethodNonce = $result['paymentMethodNonce'];
                $paymentCode = $this->getVaultPaymentCode();
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Sorry, but something went wrong'));
            }
        }
        $requestData['payment'][$paymentCode]['additional']
        [self::PAYMENT_METHOD_NONCE] = $paymentMethodNonce;
        return parent::processProfileByRequestData($requestData);
    }

    /**
     * @return string
     */
    public function getVaultPaymentCode()
    {
        return 'braintree_cc_vault';
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
