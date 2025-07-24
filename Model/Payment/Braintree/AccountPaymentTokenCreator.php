<?php
/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Braintree\CreditCard;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use PayPal\Braintree\Gateway\Config\Config;

/**
 * Class for creation vault payment token for Braintree payment account.
 */
class AccountPaymentTokenCreator
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Config
     */
    private $config;

    /**
     * AccountPaymentTokenCreator constructor.
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json $jsonSerializer
     * @param EncryptorInterface $encryptor
     * @param ObjectManagerInterface $objectManager
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $jsonSerializer,
        EncryptorInterface $encryptor,
        ObjectManagerInterface $objectManager,
        ModuleManager $moduleManager
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->encryptor = $encryptor;
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->config = $objectManager->get(Config::class);
        }
    }

    /**
     * Creates vault payment token from credit card data from Braintree API response.
     *
     * @param CreditCard $creditCard
     * @param int|null $customerId
     * @return PaymentTokenInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function create(CreditCard $creditCard, ?int $customerId = null)
    {
        $paymentData = [
            'payment_token' => $creditCard->token,
            'additional' => [
                'type' => $this->getCreditCardType($creditCard->cardType),
                'expirationDate' => $creditCard->expirationDate,
                'maskedCC' => $creditCard->last4
            ]
        ];

        $paymentToken = $this->paymentTokenFactory
            ->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($paymentData['payment_token']);
        $paymentToken->setCustomerId($customerId);
        $paymentToken->setPaymentMethodCode('braintree');
        $paymentToken->setTokenDetails($this->jsonSerializer->serialize($paymentData));
        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        return $paymentToken;
    }

    /**
     * Generates public hash for payment token.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    private function generatePublicHash(PaymentTokenInterface $paymentToken): string
    {
        $hashKey = $paymentToken->getGatewayToken();
        $hashKey .= $paymentToken->getCustomerId();
        $hashKey .= 'braintreecard' . $paymentToken->getTokenDetails();
        return $this->encryptor->getHash($hashKey);
    }

    /**
     * Maps credit card type from braintree to Magento.
     *
     * @param string $type
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getCreditCardType($type)
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCcTypesMapper();

        return $mapper[$replaced];
    }
}
