<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Quote\Model\Quote;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use TNW\Stripe\Model\Adapter\StripeAdapterFactory;
use TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Engine\Stripe as StripeEngine;
use TNW\Subscriptions\Model\Payment\Stripe\TokenExtractor;

/**
 * Class Stripe - used to save the stripe payed subscription profile
 */
class Stripe extends Base
{
    /**
     * @var VaultPaymentAuthorization
     */
    private $vaultPaymentAuthorization;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var mixed
     */
    private $adapterFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var TokenExtractor
     */
    private $tokenExtractor;

    /**
     * Stripe constructor.
     * @param CreateProfile $createModel
     * @param QuoteSessionInterface $session
     * @param VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param EncryptorInterface $encryptor
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param UrlInterface $url
     * @param TokenExtractor $tokenExtractor
     */
    public function __construct(
        CreateProfile $createModel,
        QuoteSessionInterface $session,
        VaultPaymentAuthorization $vaultPaymentAuthorization,
        EncryptorInterface $encryptor,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        UrlInterface $url,
        TokenExtractor $tokenExtractor
    ) {
        $this->tokenExtractor = $tokenExtractor;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->url = $url;
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->adapterFactory = $objectManager->get(StripeAdapterFactory::class);
        }
        parent::__construct($createModel, $session);
    }

    /**
     * @param array $data
     * @throws LocalizedException
     * @throws CommandException
     */
    public function process(array $data)
    {
        if (empty($data['payment']['tnw_stripe']['method'])) {
            return;
        }
        $paymentData = $data['payment']['tnw_stripe'];
        $paymentData['method'] = 'tnw_stripe';
        $paymentData['additional_data'] = array_merge($paymentData, $paymentData['additional']);

        /** @var Quote[] $subQuotes */
        $subQuotes = $this->getSubCreateModel()->getSubQuotes();
        $quote = reset($subQuotes);
        $guestEmail = null;
        if (!$quote->getCustomerId()) {
            $guestEmail = $this->getSession()->getCustomerEmail();
        }
        $payment = json_decode(
            $paymentData['paymentMethod'],
            true
        );
        $amount = '1';
        $currency = $quote->getQuoteCurrencyCode();
        $stripeAdapter = $this->adapterFactory->create();
        if (isset($payment['object'])
            && $payment['object'] == 'payment_intent'
        ) {
            $paymentId = $payment['payment_method'];
            $paymentIntent = $stripeAdapter->retrievePaymentIntent($payment['id']);
            if (isset($paymentIntent->customer) && $paymentIntent->customer) {
                $customerId = $paymentIntent->customer;
                $stripeAdapter->updateCustomer($customerId, ['email' => $guestEmail ?: $quote->getCustomerEmail()]);
            } else {
                $cs = $stripeAdapter->customer([
                    'email' => $guestEmail ?: $quote->getCustomerEmail(),
                    'payment_method' => $paymentId,
                    'invoice_settings' => ['default_payment_method' => $paymentId],
                    'metadata' => ['site' => $this->url->getBaseUrl()]
                ]);
            }
        } else {
            $paymentId = $payment['id'];
            $cs = $stripeAdapter->customer([
                'email' => $guestEmail ?: $quote->getCustomerEmail(),
                'payment_method' => $paymentId,
                'invoice_settings' => ['default_payment_method' => $paymentId],
                'metadata' => ['site' => $this->url->getBaseUrl()]
            ]);
        }
        if (isset($cs)) {
            $customerId = $cs->id;
        }
        $params = [
            StripeEngine::CUSTOMER => $customerId,
            StripeEngine::AMOUNT => $this->formatPrice($amount),
            StripeEngine::CURRENCY => $currency,
            StripeEngine::PAYMENT_METHOD_TYPES => ['card'],
            StripeEngine::CONFIRMATION_METHOD => 'manual',
            StripeEngine::CAPTURE_METHOD => 'manual',
            StripeEngine::SETUP_FUTURE_USAGE => 'off_session'
        ];
        if (isset($payment['object'])
            && $payment['object'] == 'payment_intent'
        ) {
            $paymentMethod = $payment['payment_method'];
            $params[StripeEngine::PAYMENT_METHOD] = $paymentMethod;
            $paymentData['cc_token'] = $payment['id'];
            $paymentData['additional_data']['cc_token'] = $payment['id'];
        } else {
            $params[StripeEngine::PAYMENT_METHOD] = $paymentId;
            $paymentIntent = $stripeAdapter->createPaymentIntent($params);
            $paymentMethod = $paymentIntent->payment_method;
            $paymentData['cc_token'] = $paymentMethod;
            $paymentData['additional_data']['cc_token'] = $paymentMethod;
            $paymentData['additional_data']['customer'] = $customerId;
        }
        if (isset($payment['object'])
            && $payment['object'] == 'payment_intent'
            && $payment['status'] == 'requires_confirmation'
        ) {
            $magentoCustomerId = $quote->getCustomerId() ?? 0;
            $transactionData = [
                'customer' => $customerId,
                'charges' => false,
                'payment_method' => $payment['payment_method']
            ];
            $paymentToken = $this->tokenExtractor
                ->getVaultPaymentToken($paymentData, $transactionData, $magentoCustomerId);

            $tempPaymentMethod = $paymentData['paymentMethod'];
        } else {
            $result = $this->vaultPaymentAuthorization->processPreAuthForTrial($paymentData, $quote, $guestEmail);
            $paymentToken = $result['payment_token'];
        }
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $paymentToken->setCustomerId($quote->getCustomerId());
        $paymentToken->setPaymentMethodCode('tnw_stripe');
        $this->paymentTokenRepository->save($paymentToken);
        foreach ($subQuotes as $subQuote) {
            $subQuote->getPayment()
                ->setAdditionalInformation('cc_number', $paymentData['cc_last_4'])
                ->setAdditionalInformation('customer_id', $subQuote->getCustomerId())
                ->setMethod('tnw_stripe_vault')
                ->setAdditionalInformation('public_hash', $paymentToken->getPublicHash())
                ->setCcType($paymentData['additional']['cc_type'])
                ->setCcLast4($paymentData['cc_last_4'])
                ->setCcExpMonth($paymentData['additional']['cc_exp_month'])
                ->setCcExpYear($paymentData['additional']['cc_exp_year']);
            if (isset($tempPaymentMethod)) {
                $subQuote->getPayment()
                    ->setAdditionalInformation('cc_3ds', true)
                    ->setAdditionalInformation('paymentMethod', $paymentData['paymentMethod']);
            }
        }

        $this->getSubCreateModel()->setNeedCollect(true);
    }

    /**
     * @param $paymentToken
     * @return string
     */
    protected function generatePublicHash($paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * @param $price
     * @return mixed
     */
    public function formatPrice($price)
    {
        $price = sprintf('%.2F', $price);

        return str_replace('.', '', $price);
    }
}
