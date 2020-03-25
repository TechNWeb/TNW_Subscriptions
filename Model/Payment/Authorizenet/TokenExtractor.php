<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Authorizenet;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CreditCardTokenFactory;

/**
 * Class TokenExtractor
 * @package TNW\Subscriptions\Model\Payment\Authorizenet
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
    private $config;

    /**
     * @var mixed
     */
    private $transferFactory;

    /**
     * @var mixed
     */
    private $client;

    /**
     * TokenExtractor constructor.
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        CreditCardTokenFactory $creditCardTokenFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->paymentTokenFactory = $creditCardTokenFactory;
        if ($moduleManager->isEnabled("TNW_AuthorizeCim")) {
            $this->client = $objectManager->get(
                "TNW\AuthorizeCim\Gateway\Http\Client\CreateCustomerProfileFromTransaction"
            );
            $this->transferFactory = $objectManager->get("TNW\AuthorizeCim\Gateway\Http\TransferFactory");
            $this->subjectReader = $objectManager->get("TNW\AuthorizeCim\Gateway\Helper\SubjectReader ");
            $this->config = $objectManager->get("TNW\AuthorizeCim\Gateway\Config\Config");
        }
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
        $transactionId = $transactionAuth->getTransactionResponse()->getTransId();
        $maskedCC = $transactionAuth->getTransactionResponse()->getAccountNumber();
        $data = [
            'trans_id' => $transactionId,
            'store_id' => $quote->getStoreId()
        ];
        $transferObject = $this->transferFactory->create($data);
        $responseCC = $this->client->placeRequest($transferObject);

        $transaction = $this->subjectReader->readTransaction($responseCC);
        return [
            'payment_token' => $this->getVaultPaymentToken($transaction, $paymentData, $maskedCC),
            'transaction_id' => $transactionId
        ];
    }

    /**
     * @param $transaction
     * @param $paymentData
     * @param $maskedCC
     * @return PaymentTokenInterface
     */
    private function getVaultPaymentToken($transaction, $paymentData, $maskedCC)
    {
        $profileId = $transaction->getCustomerProfileId();
        $paymentProfileIdList = $transaction->getCustomerPaymentProfileIdList() ? : [];
        $gateWayToken = sprintf('%s/%s', $profileId, reset($paymentProfileIdList));

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create()
            ->setExpiresAt($this->_getExpirationDate($paymentData))
            ->setGatewayToken($gateWayToken);

        $paymentToken->setTokenDetails($this->_convertDetailsToJSON([
            'type' => $paymentData['additional_data']['cc_type'],
            'maskedCC' => str_replace('XXXX', '', $maskedCC),
            'expirationDate' => sprintf(
                '%s/%s',
                $paymentData['additional_data']['cc_exp_month'],
            $paymentData['additional_data']['cc_exp_year']
            )
        ]));

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
        $json = \Zend_Json::encode($details);
        return $json ? $json : '{}';
    }
}
