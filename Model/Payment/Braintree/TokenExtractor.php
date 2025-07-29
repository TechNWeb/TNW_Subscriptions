<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class TokenExtractor - braintree
 */
class TokenExtractor
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var mixed
     */
    private $serializer;

    /**
     * @var mixed
     */
    private $config;

    /**
     * @var mixed
     */
    private $subjectReader;

    /**
     * TokenExtractor constructor.
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param ModuleManager $moduleManager
     * @param ObjectManager $objectManager
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        ModuleManager $moduleManager,
        ObjectManager $objectManager,
        ?SerializerInterface $serializer = null
    ) {
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->config = $objectManager->get(\PayPal\Braintree\Gateway\Config\Config::class);
            $this->subjectReader = $objectManager->get(\PayPal\Braintree\Gateway\Helper\SubjectReader::class);
        }
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->serializer = $serializer ?: $objectManager->get(SerializerInterface::class);
    }

    /**
     * @param $response
     * @param null $quote
     * @param null $paymentData
     * @return array
     * @throws \Exception
     */
    public function getPaymentTokenWithTransactionId($response, $quote = null, $paymentData = null)
    {
        $transaction = $this->subjectReader->readTransaction($response);
        return [
            'payment_token' => $this->getVaultPaymentToken($transaction),
            'transaction_id' => $transaction->id
        ];
    }

    /**
     * @param $transaction
     * @return PaymentTokenInterface|null
     * @throws \Exception
     */
    protected function getVaultPaymentToken($transaction)
    {
        // Check token existing in gateway response
        $token = $transaction->creditCardDetails->token;
        if (empty($token)) {
            return null;
        }

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($transaction));

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->getCreditCardType($transaction->creditCardDetails->cardType),
            'maskedCC' => $transaction->creditCardDetails->last4,
            'expirationDate' => $transaction->creditCardDetails->expirationDate
        ]));

        return $paymentToken;
    }

    /**
     * @param $transaction
     * @return string
     * @throws \Exception
     */
    private function getExpirationDate($transaction)
    {
        $expDate = new \DateTime(
            $transaction->creditCardDetails->expirationYear
            . '-'
            . $transaction->creditCardDetails->expirationMonth
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
    }

    /**
     * Get type of credit card mapped from Braintree
     *
     * @param string $type
     * @return array
     */
    private function getCreditCardType($type)
    {
        $replaced = str_replace(' ', '-', strtolower($type));
        $mapper = $this->config->getCcTypesMapper();

        return $mapper[$replaced];
    }
}
