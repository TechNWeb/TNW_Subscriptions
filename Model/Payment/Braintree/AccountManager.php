<?php
/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Braintree\Result\Error;
use Braintree\Result\Successful;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Quote\Api\Data\AddressInterface;
use PayPal\Braintree\Gateway\Http\TransferFactory;
use TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer;

/**
 * Class manages Braintree payment acounts.
 */
class AccountManager
{
    /**
     * @var TransactionCustomer
     */
    private $transactionCustomer;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * AccountManager constructor.
     *
     * @param TransactionCustomer $transactionCustomer
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        TransactionCustomer $transactionCustomer,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->transactionCustomer = $transactionCustomer;
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->transferFactory = $objectManager->get(TransferFactory::class);
        }
    }

    /**
     * Creates Braintree payment account from billing address and payment method nonce.
     *
     * @param AddressInterface $billingAddress
     * @param string $paymentMethodNonce
     * @param int|null $storeId
     * @return Successful
     * @throws PaymentException
     * @throws ClientException
     * @throws ConverterException
     */
    public function createFromBillingAddress(
        AddressInterface $billingAddress,
        string $paymentMethodNonce,
        ?int $storeId = null
    ) {
        $transfer = $this->buildTransferObject($billingAddress, $paymentMethodNonce, $storeId);
        $response = $this->transactionCustomer->placeRequest($transfer);
        $this->validateResponse($response['object']);
        return $response['object'];
    }

    /**
     * Builds transfer object for create pament account request.
     *
     * @param AddressInterface $billingAddress
     * @param string $paymentMethodNonce
     * @param int|null $storeId
     * @return TransferInterface
     */
    private function buildTransferObject(
        AddressInterface $billingAddress,
        string $paymentMethodNonce,
        ?int $storeId = null
    ) {
        return $this->transferFactory->create([
            'firstName' => $billingAddress->getFirstname(),
            'lastName' => $billingAddress->getLastname(),
            'email' => $billingAddress->getEmail(),
            'phone' => $billingAddress->getTelephone(),
            'paymentMethodNonce' => $paymentMethodNonce,
            'store_id' => $storeId
        ]);
    }

    /**
     * Validates create payment account response.
     *
     * @param Error|Successful $response
     * @return void
     * @throws PaymentException
     */
    private function validateResponse($response)
    {
        if ($response instanceof Error) {
            $errors = [];
            foreach ($response->errors->deepAll() as $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }
            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
        }
    }
}
