<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Braintree\Transaction;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Braintree\Gateway\Config\Config;
use Magento\Braintree\Gateway\SubjectReader;

/**
 * Class TokenExtractor
 * @package TNW\Subscriptions\Model\Payment\Braintree
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
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * TokenExtractor constructor.
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @param Json|null $serializer
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Config $config,
        SubjectReader $subjectReader,
        Json $serializer = null
    ) {
        $this->config = $config;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->subjectReader = $subjectReader;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
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
     * @param Transaction $transaction
     * @return PaymentTokenInterface|null
     * @throws \Exception
     */
    protected function getVaultPaymentToken(Transaction $transaction)
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
     * @param Transaction $transaction
     * @return string
     * @throws \Exception
     */
    private function getExpirationDate(Transaction $transaction)
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
