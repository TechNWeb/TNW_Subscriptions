<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Authorizenet;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\CreditCardTokenFactory;

/**
 * Class TokenExtractor - authorizenet
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
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * TokenExtractor constructor.
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param CreditCardTokenFactory $creditCardTokenFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param SerializerInterface $serializer
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        PaymentTokenManagementInterface $tokenManagement,
        CreditCardTokenFactory $creditCardTokenFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        SerializerInterface $serializer,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->paymentTokenFactory = $creditCardTokenFactory;
        $this->tokenManagement = $tokenManagement;
        if ($moduleManager->isEnabled("TNW_AuthorizeCim")) {
            $this->client = $objectManager->get(
                \TNW\AuthorizeCim\Gateway\Http\Client\CreateCustomerProfileFromTransaction::class
            );
            $this->transferFactory = $objectManager->get(\TNW\AuthorizeCim\Gateway\Http\TransferFactory::class);
            $this->subjectReader = $objectManager->get(\TNW\AuthorizeCim\Gateway\Helper\SubjectReader::class);
            $this->config = $objectManager->get(\TNW\AuthorizeCim\Gateway\Config\Config::class);
        }

        $this->serializer = $serializer;
        $this->customerRepository = $customerRepository;
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
        $customerId = $quote ? $quote->getCustomerId() : 0;
        $transaction = $this->subjectReader->readTransaction($responseCC);
        return [
            'payment_token' => $this->getVaultPaymentToken($transaction, $paymentData, $maskedCC, $customerId),
            'transaction_id' => $transactionId
        ];
    }

    /**
     * @param $transaction
     * @param $paymentData
     * @param $maskedCC
     * @param int $customerId
     * @return PaymentTokenInterface|null
     */
    private function getVaultPaymentToken($transaction, $paymentData, $maskedCC, $customerId = 0)
    {
        $profileId = $transaction->getCustomerProfileId();
        $paymentProfileIdList = $transaction->getCustomerPaymentProfileIdList() ?: [];
        $gateWayToken = sprintf('%s/%s', $profileId, reset($paymentProfileIdList));

        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('customer_profile_id', $profileId);
            $this->customerRepository->save($customer);
        }

        if (!$paymentToken = $this->tokenManagement->getByGatewayToken(
            $gateWayToken,
            'tnw_authorize_cim',
            $customerId
        )) {
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
