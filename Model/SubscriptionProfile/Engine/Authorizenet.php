<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Authorizenet Engine
 */
class Authorizenet extends Base
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
     * @var bool
     */
    private $isRebill = false;

    /**
     * @var \Magento\Vault\Model\PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization
     */
    private $vaultPaymentAuthorization;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $manager;

    /**
     * Authorizenet constructor.
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     * @param \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
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
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
    ) {
        parent::__construct(
            $config,
            $context,
            $cartManagement,
            $persistor,
            $zeroTotalValidator
        );
        $this->manager = $manager;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->paymentTokenManagement = $paymentTokenManagement;
        if ($moduleManager->isEnabled("TNW_AuthorizeCim")) {
            $this->transferFactory = $objectManager->get("TNW\AuthorizeCim\Gateway\Http\TransferFactory");
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
            $expirationDate = explode('/' , $cardDetails['expirationDate']);
        }
        if (!$cardDetails && !isset($additionalInfo[OrderPaymentInterface::CC_TYPE]) && !$expirationDate) {
            $cardDetails = [
                'type' => $payment->getCcType()
            ];
            $expirationDate = [$payment->getCcExpMonth(), $payment->getCcExpYear()];
        }
        $result = [
            'encoded_payment_additional_info' => [
                OrderPaymentInterface::CC_TYPE => isset($additionalInfo[OrderPaymentInterface::CC_TYPE])
                    ? $additionalInfo[OrderPaymentInterface::CC_TYPE]
                    : $cardDetails['type'],
                OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
                OrderPaymentInterface::CC_EXP_MONTH => isset($additionalInfo[OrderPaymentInterface::CC_EXP_MONTH])
                    ? $additionalInfo[OrderPaymentInterface::CC_EXP_MONTH]
                    : $expirationDate[0],
                OrderPaymentInterface::CC_EXP_YEAR => isset($additionalInfo[OrderPaymentInterface::CC_EXP_YEAR])
                    ? $additionalInfo[OrderPaymentInterface::CC_EXP_YEAR]
                    : $expirationDate[1],
                'authorizenet_data' => $additionalInfo
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

        if (isset($result['authorizenet_data']['public_hash'])) {
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
        }
        $publicHash = $gateWayToken ? $gateWayToken->getPublicHash() : '';
        $result = isset($addtionalInfo['authorizenet_data']) ? $addtionalInfo['authorizenet_data'] : $addtionalInfo;
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
        //TODO: resolve if vault method
        return 'tnw_authorize_cim';
    }

    /**
     *
     */
    public function setRebillProcessFlag()
     {
         $this->isRebill = true;
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
        if (empty($requestData['payment'][$this->getPaymentMethodCode()]['method'])) {
            return $this;
        }

        $customer = $this->getProfile()->getCustomer();
        if (!$customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            return $this;
        }

        /** @var string[] $additionalData */
        $additionalData = $requestData['payment'][$this->getPaymentMethodCode()]['additional'];

        $paymentData = $requestData['payment'][$this->getPaymentMethodCode()];
        $paymentData['method'] = $this->getPaymentMethodCode();
        $paymentData['additional_data'] = array_merge($paymentData, $additionalData);

        $result = $this->vaultPaymentAuthorization->processPreAuthForTrial(
            $paymentData,
            $this->manager->getTempQuote($this->getProfile())
        );
        $paymentToken = $result['payment_token'];
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $paymentToken->setCustomerId($customer->getId());
        $paymentToken->setPaymentMethodCode('tnw_authorize_cim');
        $this->paymentTokenRepository->save($paymentToken);


        $this->getProfile()->getPayment()
            ->setEngineCode($this->getPaymentMethodCode())
            ->setPaymentToken($paymentToken->getGatewayToken())
            ->setEncodedPaymentAdditionalInfo([
                OrderPaymentInterface::CC_TYPE => $additionalData['cc_type'],
                OrderPaymentInterface::CC_LAST_4 => $paymentData['cc_last_4'],
                OrderPaymentInterface::CC_EXP_MONTH => $additionalData['cc_exp_month'],
                OrderPaymentInterface::CC_EXP_YEAR => $additionalData['cc_exp_year'],
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
            $payment->importData(['method' => \Magento\Payment\Model\Method\Free::PAYMENT_METHOD_FREE_CODE]);
            $payment->setAdditionalInformation([]);
        } else {
            parent::validatePayment($quote);
        }
    }

    /**
     * @param $paymentToken
     * @return string
     */
    protected function generatePublicHash($paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }
}
