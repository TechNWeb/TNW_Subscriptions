<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

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
     * TokenExtractor constructor.
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
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
        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create();
        $token = $response->getData(\Magento\Paypal\Model\Payflowpro::PNREF);
        $paymentToken->setGatewayToken($token);
        $payment = $quote->getPayment();
        $paymentToken->setTokenDetails(
            json_encode($payment
                ->getAdditionalInformation(\Magento\Paypal\Model\Payflow\Transparent::CC_DETAILS)
            )
        );
        $paymentToken->setExpiresAt(
            $this->getExpirationDate($payment)
        );

        return [
            'payment_token' => $paymentToken,
            'transaction_id' => $token
        ];
    }

    /**
     * @param $payment
     * @return string
     * @throws \Exception
     */
    private function getExpirationDate($payment)
    {
        $expDate = new \DateTime(
            $payment->getCcExpYear()
            . '-'
            . $payment->getCcExpMonth()
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }
}
