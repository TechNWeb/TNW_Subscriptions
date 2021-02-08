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

/**
 * Class CyberSource - model for processing the cybersource payments
 */
class CyberSource extends Base
{
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
    private $requestHelper;

    /**
     * @var \Magento\Vault\Model\PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * CyberSource constructor.
     * @param \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
     * @param \TNW\Subscriptions\Model\Config $config
     * @param \TNW\Subscriptions\Model\Context $context
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Framework\App\Request\DataPersistorInterface $persistor
     * @param \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     * @param \TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Gateway\Config\Config $cyberSourceConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
     * @param \TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Model\VaultPlugin $vaultChecker
     */
    public function __construct(
        \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement,
        \TNW\Subscriptions\Model\Config $config,
        \TNW\Subscriptions\Model\Context $context,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\App\Request\DataPersistorInterface $persistor,
        \Magento\Payment\Model\Checks\ZeroTotal $zeroTotalValidator,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
        \TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Gateway\Config\Config $cyberSourceConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $manager,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory,
        \TNW\Subscriptions\Plugin\CyberSource\SecureAcceptance\Model\VaultPlugin $vaultChecker
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
        $this->paymentTokenFactory = $paymentTokenFactory;
        $cyberSourceConfig->reBillProcess();
        $vaultChecker->setIsReBill();
        if ($moduleManager->isEnabled("CyberSource_Core")
            && $moduleManager->isEnabled("CyberSource_SecureAcceptance")
        ) {
            $this->transferFactory = $objectManager->get(\CyberSource\Core\Gateway\Http\TransferFactory::class);
            $this->requestHelper = $objectManager->get(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class);
        }
        $this->transactionCustomer = $transactionCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        $cybersourceToken = $payment->getAdditionalInformation('cybersourse_token');
        $cyberSourceData = $payment->getAdditionalInformation();
        $result = parent::getProfilePaymentInfo($payment);
        $result['token_hash'] = $cybersourceToken;
        $result['encoded_payment_additional_info'] = [
            OrderPaymentInterface::CC_TYPE => $payment->getCcType(),
            OrderPaymentInterface::CC_LAST_4 => $payment->getCcLast4(),
            OrderPaymentInterface::CC_EXP_MONTH => $payment->getCcExpMonth(),
            OrderPaymentInterface::CC_EXP_YEAR => $payment->getCcExpYear(),
            'cybersource_data' =>$cyberSourceData
        ];
        return $result;
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return ConfigProvider::CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];

        $result[OrderPaymentInterface::METHOD] = ConfigProvider::CODE . '_cc_vault';

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        $profilePayment = $profile->getPayment();
        $result = !empty($profile->getPayment()->getDecodedPaymentAdditionalInfo())
            ? $profile->getPayment()->getDecodedPaymentAdditionalInfo()
            : [];

        if (isset($result['cybersource_data'])) {
            $cyberSourceData = $result['cybersource_data'];
            if (isset($cyberSourceData['cybersource_token'])) {
                $vaultToken = $this->paymentTokenManagement->getByGatewayToken(
                    $cyberSourceData['cybersource_token'],
                    ConfigProvider::CODE,
                    $profile->getCustomerId()
                );
                if (!$vaultToken) {
                    $vaultToken = $this->createNewPaymentToken($result, $profile);
                }
                $cyberSourceData['public_hash'] = $vaultToken->getPublicHash();
            }

        } else {
            $cyberSourceData = [];
            $vaultToken = $this->paymentTokenManagement->getByGatewayToken(
                $profilePayment->getPaymentToken(),
                ConfigProvider::CODE,
                $profile->getCustomerId()
            );
            if (!$vaultToken) {
                $vaultToken = $this->paymentTokenManagement->getByPublicHash(
                    $profilePayment->getPaymentToken(),
                    $profile->getCustomerId()
                );
            }
            $cyberSourceData['public_hash'] = $vaultToken
                ? $vaultToken->getPublicHash()
                : $profilePayment->getPaymentToken();
        }
        if (!isset($result['cybersource_data'])) {
            $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
                $profilePayment->getPaymentToken(),
                ConfigProvider::CODE,
                $profile->getCustomerId()
            );
            $cyberSourceData = [
                'cybersource_token' => $profilePayment->getPaymentToken(),
                'cardNumber' => str_replace('-', '', $result['cc_last_4']),
                'cardType' => $this->requestHelper->getCardType($result['cc_type']),
                "is_active_payment_token_enabler" => true,
                'token_data' => [
                    "payment_token" => $profilePayment->getPaymentToken(),
                    "card_type" => $this->requestHelper->getCardType($result['cc_type']),
                    "cc_last4" => $result['cc_last_4'],
                    "card_expiry_date" => $result['cc_exp_month'] . '-' . $result['cc_exp_year']
                ],
                'public_hash' => $paymentToken ? $paymentToken->getPublicHash() : ''
            ];
            if ($paymentToken) {
                $cyberSourceData['extension_attributes'] = json_decode($paymentToken->getTokenDetails(), true);
            }
        }
        $cyberSourceData['customer_id'] = $profile->getCustomerId();
        return array_merge(
            $cyberSourceData,
            [OrderPaymentInterface::METHOD => ConfigProvider::CODE . '_cc_vault']
        );
    }

    /**
     * @param $requestData
     * @return $this|Base|EngineInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function processProfileByRequestData($requestData)
    {
        if (empty($requestData['payment'][ConfigProvider::CODE]['method'])
            && empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            return $this;
        }

        if (empty($requestData['payment'][$this->getVaultPaymentCode()]['method'])) {
            $paymentToken = $this->paymentTokenFactory->create('card');
            $paymentToken->setPublicHash($this->generateNewTokenPublicHash(
                $requestData['payment'][ConfigProvider::CODE],
                $this->getProfile()->getCustomerId()
            ));
            $paymentToken->setGatewayToken($requestData['payment'][ConfigProvider::CODE]['payment_token']);
            $paymentToken->setCustomerId($this->getProfile()->getCustomerId());
            $paymentToken->setPaymentMethodCode('chcybersource');
            $paymentToken->setTokenDetails(
                $this->getNewTokenDetails($requestData['payment'][ConfigProvider::CODE]['additional'])
            );
            $paymentToken->setIsActive(true);
            $paymentToken->setIsVisible(true);
            $paymentToken->setExpiresAt(
                $this->getExpiresAt($requestData['payment'][ConfigProvider::CODE]['additional'])
            );
            $this->paymentTokenRepository->save($paymentToken);
            $requestData['payment'][ConfigProvider::CODE]['additional']['public_hash'] = $paymentToken->getPublicHash();
        }
        return parent::processProfileByRequestData($requestData);
    }

    /**
     * @return string
     */
    public function getVaultPaymentCode()
    {
        return 'chcybersource_cc_vault';
    }

    /**
     * @param $paymentToken
     * @return string
     */
    protected function generatePublicHash($paymentToken)
    {
        $result = $paymentToken->getPublicHash();
        if ($result) {
            $hashKey = $paymentToken->getGatewayToken();
            if ($paymentToken->getCustomerId()) {
                $hashKey = $paymentToken->getCustomerId();
            }

            $hashKey .= $paymentToken->getPaymentMethodCode()
                . $paymentToken->getType()
                . $paymentToken->getTokenDetails();

            $result = $this->encryptor->getHash($hashKey);
        }
        return $result;
    }

    /**
     * @param $paymentToken
     * @return string
     */
    protected function generateNewTokenPublicHash($paymentData, $customerId)
    {
        $hashKey = $paymentData['payment_token'];
        $hashKey .= $customerId;
        $hashKey .= 'chcybersource'
            . 'card'
            . $this->getNewTokenDetails($paymentData['additional']);

        return $this->encryptor->getHash($hashKey);
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
     * @param $data
     * @param $profile
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    private function createNewPaymentToken($data, $profile)
    {
        $newToken = $this->paymentTokenManagement->getByGatewayToken(
            $data['cybersource_data']['cybersource_token'],
            ConfigProvider::CODE,
            null
        );
        if ($newToken) {
            $newToken->setCustomerId($profile->getCustomerId());
        } else {
            $newTokenData = [
                'additional' => array_merge(
                    $data,
                    ['cc_number' => $data['cybersource_data']['cardNumber']]
                ),
                'payment_token' => $data['cybersource_data']['cybersource_token']
            ];
            $newToken = $this->paymentTokenFactory->create()
                ->setTokenDetails($this->getNewTokenDetails($newTokenData['additional']))
                ->setCustomerId($profile->getCustomerId())
                ->setPaymentMethodCode(ConfigProvider::CODE)
                ->setIsVisible(1)
                ->setIsActive(1)
                ->setType('card')
                ->setGatewayToken($data['cybersource_data']['cybersource_token'])
                ->setPublicHash($this->generateNewTokenPublicHash(
                    $newTokenData,
                    $profile->getCustomerId()
                ))
                ->setExpiresAt($this->getExpiresAt($newTokenData['additional']));
        }
        return $this->paymentTokenRepository->save($newToken);
    }

    /**
     * @param $paymentData
     * @return false|int
     */
    private function getExpiresAt($paymentData)
    {
        return strtotime($paymentData['cc_exp_year'] . '-' . $paymentData['cc_exp_month'] . '-01');
    }

    /**
     * @param $paymentData
     * @return false|string
     */
    private function getNewTokenDetails($paymentData)
    {
        //TODO:: get valid pyament method title
        return json_encode(
            [
                "type" => $paymentData['cc_type'],
                "maskedCC" => "****-****-****-" . substr($paymentData['cc_number'], -4),
                "incrementId" => null,
                "expirationDate" => $paymentData['cc_exp_month'] . "/" . $paymentData['cc_exp_year'],
                "title" => "CyberSource Stored Cards"
            ]
        );
    }
}
