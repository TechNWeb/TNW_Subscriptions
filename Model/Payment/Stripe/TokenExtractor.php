<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Stripe;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Class TokenExtractor - stripe
 */
class TokenExtractor
{
    /**
     * @var CreditCardTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var mixed
     */
    private $subjectReader;

    /**
     * @var mixed
     */
    private $transferFactory;

    /**
     * @var mixed
     */
    private $client;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var mixed
     */
    private $gatewayConfig;

    /**
     * TokenExtractor constructor.
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManagement,
        CreditCardTokenFactory $creditCardTokenFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        SerializerInterface $serializer
    ) {
        $this->paymentTokenFactory = $creditCardTokenFactory;
        $this->tokenManagement = $tokenManagement;
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->client = $objectManager->get(
                \TNW\Stripe\Gateway\Http\Client\TransactionCustomer::class
            );
            $this->gatewayConfig = $objectManager->get(\TNW\Stripe\Gateway\Config\Config::class);
            $this->transferFactory = $objectManager->get(\TNW\Stripe\Gateway\Http\TransferFactory::class);
            $this->subjectReader = $objectManager->get(\TNW\Stripe\Gateway\Helper\SubjectReader::class);
        }
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
        $transactionAuth = $this->subjectReader->readTransaction($response);
        return [
            'payment_token' => $this->getVaultPaymentToken($paymentData, $transactionAuth, $quote->getCustomerId()),
            'transaction_id' => $transactionAuth['id']
        ];
    }

    /**
     * @param $paymentData
     * @param $transaction
     * @param int $customerId
     * @return PaymentTokenInterface|null
     */
    private function getVaultPaymentToken($paymentData, $transaction, $customerId = 0)
    {
        $gateWayToken = $transaction['customer'] . '/' . $transaction['payment_method'];

        if (!$paymentToken = $this->tokenManagement->getByGatewayToken(
            $gateWayToken,
            'tnw_stripe',
            $customerId
        )) {
            $last4 = isset($paymentData['cc_last_4']) ? $paymentData['cc_last_4'] : '';
            /** @var PaymentTokenInterface $paymentToken */
            $paymentToken = $this->paymentTokenFactory->create()
                ->setExpiresAt($this->_getExpirationDate($paymentData))
                ->setGatewayToken($gateWayToken);
            $ccMap = $this->gatewayConfig->getCcTypesMapper();
            $transactionCharges = $transaction['charges'];
            if (is_array($transactionCharges) && isset($transactionCharges['data']) && !$last4) {
                $last4 = $transactionCharges['data'][0]['payment_method_details']['card']['last4'];
            } elseif (!$last4) {
                foreach ($transactionCharges as $charge) {
                    $details = $charge->__get('payment_method_details');
                    $cardData = $details->__get('card');
                    $last4 = $cardData->__get('last4');
                }
            }
            $paymentToken->setTokenDetails($this->_convertDetailsToJSON([
                'type' => $ccMap[$paymentData['additional_data']['cc_type']],
                'maskedCC' => $last4,
                'expirationDate' => sprintf(
                    '%s/%s',
                    $paymentData['additional_data']['cc_exp_month'],
                    $paymentData['additional_data']['cc_exp_year']
                )
            ]));
        }

        return $paymentToken;
    }

    /**
     * @param $payment
     * @return string
     */
    private function _getExpirationDate($payment)
    {
        $time = sprintf(
            '%s-%s-01 00:00:00',
            trim($payment['additional_data']['cc_exp_year']),
            trim($payment['additional_data']['cc_exp_month'])
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
