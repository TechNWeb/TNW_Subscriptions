<?php
/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Class for processing payment information when using Braintree payment method.
 */
class ProcessPaymentInformation
{
    /**
     * @var AccountManager
     */
    private $braintreeAccountManager;

    /**
     * @var AccountPaymentTokenCreator
     */
    private $accountPaymentTokenCreator;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * ProcessPaymentInformation constructor.
     *
     * @param AccountManager $braintreeAccountManager
     * @param AccountPaymentTokenCreator $accountPaymentTokenCreator
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param EncryptorInterface $encryptor
     * @param Json $jsonSerializer
     */
    public function __construct(
        AccountManager $braintreeAccountManager,
        AccountPaymentTokenCreator $accountPaymentTokenCreator,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        EncryptorInterface $encryptor,
        Json $jsonSerializer
    ) {
        $this->braintreeAccountManager = $braintreeAccountManager;
        $this->accountPaymentTokenCreator = $accountPaymentTokenCreator;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Creates Braintree payment account and stores it's vault payment token.
     *
     * @param CartInterface $quote
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface $billingAddress
     * @return void
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws PaymentException
     * @throws ClientException
     * @throws ConverterException
     */
    public function createBraintreePaymentAccountAndSavePaymentToken(
        CartInterface $quote,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress
    ) {
        if ($this->shouldCreateBraintreePaymentAccount($quote)) {
            $additionalData = $paymentMethod->getAdditionalData();
            $response = $this->braintreeAccountManager->createFromBillingAddress(
                $billingAddress,
                $additionalData['payment_method_nonce'],
                $quote->getStoreId()
            );
            $creditCard = $response->customer->paymentMethods[0];
            $paymentToken = $this->accountPaymentTokenCreator->create($creditCard, $quote->getCustomerId());
            $this->paymentTokenRepository->save($paymentToken);

            $payment = $quote->getPayment();
            $tokenDetails = $this->jsonSerializer->unserialize($paymentToken->getTokenDetails());
            $payment->setAdditionalInformation('token_hash', $this->encryptor->encrypt($creditCard->token))
                ->setAdditionalInformation('customer_id', $quote->getCustomerId())
                ->setMethod('braintree_cc_vault')
                ->setAdditionalInformation('public_hash', $paymentToken->getPublicHash())
                ->setCcType($tokenDetails['additional']['type'])
                ->setCcLast4($creditCard->last4)
                ->setCcExpMonth($creditCard->expirationMonth)
                ->setCcExpYear($creditCard->expirationYear);

            // Replace payment data for further use in
            // \TNW\Subscriptions\Model\Payment\Braintree\BraintreePaymentDataBuilder::build() method
            unset($additionalData['payment_method_nonce']);
            $additionalData['customer_id'] = $quote->getCustomerId();
            $additionalData['public_hash'] = $paymentToken->getPublicHash();
            $paymentMethod->setAdditionalData($additionalData);
        }
    }

    /**
     * Checks if Braintree payment account should be created.
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function shouldCreateBraintreePaymentAccount(CartInterface $quote) : bool
    {
        if ($quote->getBaseGrandTotal() < 0.0001) {
            if ($quote->getData('is_tnw_subscription')) {
                return true;
            }
            foreach ($quote->getItems() as $item) {
                $options = $item->getBuyRequest();
                if (isset($options['subscribe_active']) && $options['subscribe_active']) {
                    return true;
                }
            }
        }
        return false;
    }
}
