<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Cybersource;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class TokenExtractor - cybersource
 */
class TokenExtractor
{
    /**
     * @var CreditCardTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * TokenExtractor constructor.
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManagement,
        CreditCardTokenFactory $creditCardTokenFactory,
        SerializerInterface $serializer
    ) {
        $this->paymentTokenFactory = $creditCardTokenFactory;
        $this->tokenManagement = $tokenManagement;
        $this->serializer = $serializer;
    }

    /**
     * @param $response
     * @param null $quote
     * @param null $paymentData
     * @return array
     */
    public function getPaymentTokenWithTransactionId($response, $quote = null, $paymentData = null)
    {
        $paymentToken = null;
        if (isset($paymentData['additional_data']['public_hash'])) {
            $paymentToken = $this->tokenManagement->getByPublicHash(
                $paymentData['additional_data']['public_hash'],
                $quote->getCustomerId()
            );
        }
        if (!$paymentToken) {
            $paymentToken = $this->getVaultPaymentToken($quote);
        }
        return [
            'payment_token' => $paymentToken,
            'transaction_id' => $response['requestID']
        ];
    }

    /**
     * @param $quote
     * @return PaymentTokenInterface
     */
    private function getVaultPaymentToken($quote)
    {
        $quotePayment = $quote->getPayment();
        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create()
            ->setExpiresAt($this->_getExpirationDate($quotePayment->getData()))
            ->setGatewayToken($quotePayment->getAdditionalInformation('cybersource_token'));

        $tokenData = $quotePayment->getAdditionalInformation('token_data');
        $paymentToken->setTokenDetails($this->_convertDetailsToJSON([
            'type' => $quotePayment->getCcType(),
            'maskedCC' => isset($tokenData['cc_last4'])
                ? $tokenData['cc_last4']
                : $quotePayment->getAdditionalInformation('cardNumber'),
            'incrementId' => $quote->getReserverdOrderId(),
            'expirationDate' => isset($tokenData['card_expiry_date'])
                ? str_replace('-', '/', $tokenData['card_expiry_date'])
                : $quotePayment->getCcExpMonth() . '/' . $quotePayment->getCcExpYear(),
            'title' => "CyberSource Stored Cards"
        ]));
        return $paymentToken;
    }

    /**
     * @param $paymentData
     * @return string
     */
    private function _getExpirationDate($paymentData)
    {
        $time = sprintf(
            '%s-%s-01 00:00:00',
            trim($paymentData['cc_exp_year']),
            trim($paymentData['cc_exp_month'])
        );

        return date_create($time, timezone_open('UTC'))
            ->modify('+1 month')
            ->format('Y-m-d 00:00:00');
    }

    /**
     * @param $details
     * @return string
     */
    private function _convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
    }
}
