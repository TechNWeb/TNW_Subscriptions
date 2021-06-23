<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Payment\Model\Checks\ZeroTotal;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use Magento\Payment\Model\Method\Free;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class Base - base class for payment processors
 */
class Base implements EngineInterface
{
    /**
     * Config model.
     *
     * @var Config
     */
    private $config;

    /**
     * @var Context
     */
    private $context;

    /**
     * Subscription profile.
     *
     * @var SubscriptionProfileInterface
     */
    private $profile;

    /**
     * Cart management.
     *
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * Data persistor
     *
     * @var DataPersistorInterface
     */
    protected $persistor;

    /**
     * Zero total quote validator.
     *
     * @var ZeroTotal
     */
    private $zeroTotalValidator;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    protected $paymentTokenRepository;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    protected $manager;

    /**
     * @var \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization
     */
    protected $vaultPaymentAuthorization;

    /**
     * @var \Magento\Vault\Model\PaymentTokenManagement
     */
    protected $paymentTokenManagement;

    /**
     * Base constructor.
     * @param Config $config
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     * @param DataPersistorInterface $persistor
     * @param ZeroTotal $zeroTotalValidator
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
     */
    public function __construct(
        Config $config,
        Context $context,
        CartManagementInterface $cartManagement,
        DataPersistorInterface $persistor,
        ZeroTotal $zeroTotalValidator,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->manager = $manager;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->config = $config;
        $this->context = $context;
        $this->cartManagement = $cartManagement;
        $this->persistor = $persistor;
        $this->zeroTotalValidator = $zeroTotalValidator;
    }

    /**
     * Returns config object.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns context object.
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns cart management object.
     *
     * @return CartManagementInterface
     */
    public function getCartManagement()
    {
        return $this->cartManagement;
    }

    /**
     * Returns data persistor
     *
     * @return DataPersistorInterface
     */
    protected function getPersistor()
    {
        return $this->persistor;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * {@inheritdoc}
     */
    public function setProfile(SubscriptionProfileInterface $profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return ['engine_code' => $this->getVaultPaymentCode()];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function processProfile(Quote $quote)
    {
        try {
            $this->validatePayment($quote);
            $order = $this->getCartManagement()->submit($quote);
            $this->updateProfileStatus();

            return $order;
        } catch (\Exception $e) {
            $this->getProfile()->setStatus(ProfileStatus::STATUS_PAST_DUE);
            $quote->setReservedOrderId(null);
            $quote->save();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        return [];
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return '';
    }

    /**
     * @param $requestData
     * @return $this|EngineInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processProfileByRequestData($requestData)
    {
        if (!empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            return $this->processProfileByRequestDataVault($requestData);
        }
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
        $paymentToken->setType('card');
        $paymentToken->setPaymentMethodCode($this->getPaymentMethodCode());
        $this->paymentTokenRepository->save($paymentToken);
        $this->populateProfilePayment($paymentToken);
        return $this;
    }

    /**
     * @param $requestData
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processProfileByRequestDataVault($requestData)
    {
        if (empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            return $this;
        }
        $customer = $this->getProfile()->getCustomer();
        if (!$customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            return $this;
        }

        $paymentToken = $this->paymentTokenManagement->getByPublicHash(
            $requestData['payment'][$this->getVaultPaymentCode()]['additional']['publicHash'],
            $customer->getId()
        );
        /** @var string[] $additionalData */
        $additionalData = $requestData['payment'][$this->getVaultPaymentCode()]['additional'];
        $additionalData['public_hash'] = $additionalData['publicHash'];
        $paymentData = $requestData['payment'][$this->getVaultPaymentCode()];
        $paymentData['method'] = $this->getVaultPaymentCode();
        $paymentData['additional_data'] = array_merge($paymentData, $additionalData);

        if ($paymentToken->getGatewayToken() !== $this->getProfile()->getPayment()->getPaymentToken()) {
            $this->vaultPaymentAuthorization->processPreAuthForTrial(
                $paymentData,
                $this->manager->getTempQuote($this->getProfile())
            );
            $this->populateProfilePayment($paymentToken);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getVaultPaymentCode()
    {
        return '';
    }

    /**
     * @param $paymentToken
     * @return $this
     */
    protected function populateProfilePayment($paymentToken)
    {
        $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
        $expiration = explode('/', $tokenDetails['expirationDate']);
        $this->getProfile()->getPayment()
            ->setEngineCode($this->getVaultPaymentCode())
            ->setPaymentToken($paymentToken->getGatewayToken())
            ->setEncodedPaymentAdditionalInfo([
                OrderPaymentInterface::CC_TYPE => $tokenDetails['type'],
                OrderPaymentInterface::CC_LAST_4 => $tokenDetails['maskedCC'],
                OrderPaymentInterface::CC_EXP_MONTH => $expiration[0],
                OrderPaymentInterface::CC_EXP_YEAR => $expiration[1],
            ]);
        return $this;
    }

    /**
     * Validates zero total and sets free payment method to quote if validation failed.
     *
     * @param Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validatePayment(Quote $quote)
    {
        /** @var Payment $payment */
        $payment = $quote->getPayment();
        $payment->importData($this->getPaymentInfo($this->getProfile()));
        $payment->setAdditionalInformation($this->getPaymentAdditionalInfo($this->getProfile()));

        // check quote total
        if (!$this->zeroTotalValidator->isApplicable($payment->getMethodInstance(), $quote)) {
            $payment->importData(['method' => Free::PAYMENT_METHOD_FREE_CODE]);
            $payment->setAdditionalInformation([]);
        }
    }

    /**
     * Updates profile status after order processing
     */
    private function updateProfileStatus()
    {
        $status = ProfileStatus::STATUS_ACTIVE;
        $trialStartDate = $this->getProfile()->getTrialStartDate();
        $startDate = $this->getProfile()->getStartDate();
        if ($trialStartDate) {
            $date = (new \DateTime())->getTimestamp();
            $startDate = (new \DateTime($startDate))->getTimestamp();
            if ($date < $startDate) {
                $status = ProfileStatus::STATUS_TRIAL;
            }
        }
        $this->getProfile()->setStatus($status);
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
