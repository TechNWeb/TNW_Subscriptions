<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Paypal;

use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use \Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class TokenExtractor
 */
class TokenExtractor
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * TokenExtractor constructor.
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
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
        $token = $response->getData(\Magento\Paypal\Model\Payflowpro::PNREF);
        $paymentToken = false;
        if ($quote->getCustomerId() && isset($paymentData['additional_data']['public_hash'])) {
            $paymentToken = $this->paymentTokenManagement->getByPublicHash(
                $paymentData['additional_data']['public_hash'],
                $quote->getCustomerId()
            );
        }

        if (!$paymentToken) {
            /** @var PaymentTokenInterface $paymentToken */
            $paymentToken = $this->paymentTokenFactory->create();

            $paymentToken->setGatewayToken($token);
            $payment = $quote->getPayment();
            if (!$payment->getAdditionalInformation(\Magento\Paypal\Model\Payflow\Transparent::CC_DETAILS)
                && isset($paymentData['additional'])
            ) {
                $paymentToken->setTokenDetails(json_encode($paymentData['additional']));
                $paymentToken->setExpiresAt($this->getExpirationDate($paymentData['additional']));
            } else {
                $paymentToken->setTokenDetails(
                    json_encode($payment
                        ->getAdditionalInformation(\Magento\Paypal\Model\Payflow\Transparent::CC_DETAILS))
                );
                $paymentToken->setExpiresAt(
                    $this->getExpirationDate($payment->getData())
                );
            }
        }

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
    private function getExpirationDate($paymentData)
    {
        $expDate = new \DateTime(
            $paymentData['cc_exp_year']
            . '-'
            . $paymentData['cc_exp_month']
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
