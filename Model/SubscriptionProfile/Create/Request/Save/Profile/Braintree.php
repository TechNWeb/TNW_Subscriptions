<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Save payment data processor.
 */
class Braintree extends Base
{
    /**
     * @var \PayPal\Braintree\Gateway\Http\TransferFactory
     */
    private $transferFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization
     */
    private $vaultPaymentAuthorization;

    /**
     * @var \Magento\Vault\Model\PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\BraintreePaymentDataBuilder
     */
    private $braintreePaymentDataBuilder;

    /**
     * Braintree constructor.
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory
     * @param \TNW\Subscriptions\Model\Payment\Braintree\BraintreePaymentDataBuilder $braintreePaymentDataBuilder
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Vault\Model\PaymentTokenFactory $paymentTokenFactory,
        \TNW\Subscriptions\Model\Payment\Braintree\BraintreePaymentDataBuilder $braintreePaymentDataBuilder,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($createModel, $session);
        $this->braintreePaymentDataBuilder = $braintreePaymentDataBuilder;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->transactionCustomer = $transactionCustomer;
        $this->encryptor = $encryptor;
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->transferFactory = $objectManager->get(\PayPal\Braintree\Gateway\Http\TransferFactory::class);
        }
    }

    /**
     * @param array $data
     * @throws PaymentException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     * @throws \Zend_Json_Exception
     */
    public function process(array $data)
    {
        if (empty($data['payment']['braintree']['method'])) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote[] $subQuotes */
        $subQuotes = $this->getSubCreateModel()->getSubQuotes();

        $customer = reset($subQuotes)->getCustomer()->getId()
            ? reset($subQuotes)->getCustomer()
            : $this->getSubCreateModel()->getShippingAddress();

        $customerEmail = $customer->getEmail() ? $customer->getEmail() : $this->getSession()->getCustomerEmail();
        $transfer = $this->transferFactory->create([
            'firstName' => $customer->getFirstname(),
            'lastName' => $customer->getLastname(),
            'email' => $customerEmail,
            'phone' => $this->getSubCreateModel()->getShippingAddress()->getTelephone(),
            'paymentMethodNonce' => $data['payment']['braintree']['nonce'],
            'store_id' => $this->getSession()->getStoreId()
        ]);

        /** @var \Braintree\Result\Error|\Braintree\Result\Successful $response */
        $response = $this->transactionCustomer->placeRequest($transfer);
        if (isset($this->transferFactory)
            && class_exists(\Braintree\Result\Error::class)
            && $response['object'] instanceof \Braintree\Result\Error
        ) {
            $errors = [];
            foreach ($response->errors->deepAll() as $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
        }

        /** @var \Braintree\CreditCard $paymentMethod */
        $paymentMethod = $response['object']->customer->paymentMethods[0];
        $quote = reset($subQuotes);
        $paymentData = [
            'payment_token' => $paymentMethod->token,
            'additional' => [
                'type' => $data['payment']['braintree']['additional']['cc_type'],
                'expirationDate' => $paymentMethod->expirationDate,
                'maskedCC' => $paymentMethod->last4
            ]
        ];

        $paymentToken = $this->paymentTokenFactory->create('card');
        $paymentToken->setPublicHash($this->generatePublicHash($paymentData, $quote->getCustomerId()));
        $paymentToken->setGatewayToken($paymentData['payment_token']);
        $paymentToken->setCustomerId($quote->getCustomerId());
        $paymentToken->setPaymentMethodCode('braintree');
        $paymentToken->setTokenDetails($this->getTokenDetails($paymentData));
        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);
        $this->paymentTokenRepository->save($paymentToken);
        $maxAmountPreAuthorized = 0;
        /** @var \Magento\Quote\Model\Quote $subQuote */
        foreach ($subQuotes as $subQuote) {
            if ($subQuote->getGrandTotal() < 0.001) {
                if ($maxAmountPreAuthorized < $this->braintreePaymentDataBuilder->getAmount($subQuote)) {
                    $vaultPaymentData = [];
                    $vaultPaymentData['method'] = $this->getVaultMethodCode();
                    $vaultPaymentData['additional_data']['customer_id'] = $subQuote->getCustomerId();
                    $vaultPaymentData['additional_data']['public_hash'] = $paymentToken->getPublicHash();
                    $this->vaultPaymentAuthorization->processPreAuthForTrial($vaultPaymentData, $subQuote);
                    $maxAmountPreAuthorized = $this->braintreePaymentDataBuilder->getAmount($subQuote);
                }
            }
            $subQuote->getPayment()
                ->setAdditionalInformation('token_hash', $this->encryptor->encrypt($paymentMethod->token))
                ->setAdditionalInformation('customer_id', $subQuote->getCustomerId())
                ->setMethod($this->getVaultMethodCode())
                ->setAdditionalInformation('public_hash', $paymentToken->getPublicHash())
                ->setCcType($data['payment']['braintree']['additional']['cc_type'])
                ->setCcLast4($paymentMethod->last4)
                ->setCcExpMonth($paymentMethod->expirationMonth)
                ->setCcExpYear($paymentMethod->expirationYear);
        }

        $this->getSubCreateModel()->setNeedCollect(true);
    }

    /**
     * @return string
     */
    public function getVaultMethodCode()
    {
        return 'braintree_cc_vault';
    }

    /**
     * @param $paymentData
     * @param $customerId
     * @return string
     */
    protected function generatePublicHash($paymentData, $customerId)
    {
        $hashKey = $paymentData['payment_token'];
        $hashKey .= $customerId;
        $hashKey .= 'braintree'
            . 'card'
            . $this->getTokenDetails($paymentData);

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * @param $paymentData
     * @return false|string
     */
    private function getTokenDetails($paymentData)
    {
        return json_encode($paymentData['additional']);
    }
}
