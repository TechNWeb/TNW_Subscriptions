<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use \TNW\Subscriptions\Model\SubscriptionProfile\Engine\Stripe as StripeEngine;

/**
 * Class Stripe - used to save the stripe payed subscription profile
 */
class Stripe extends Base
{
    /**
     * @var \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization
     */
    private $vaultPaymentAuthorization;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \Magento\Vault\Api\PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var mixed
     */
    private $adapterFactory;

    /**
     * Stripe constructor.
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->adapterFactory = $objectManager->get(\TNW\Stripe\Model\Adapter\StripeAdapterFactory::class);
        }
        parent::__construct($createModel, $session);
    }

    /**
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function process(array $data)
    {
        if (empty($data['payment']['tnw_stripe']['method'])) {
            return;
        }
        $paymentData = $data['payment']['tnw_stripe'];
        $paymentData['method'] = 'tnw_stripe';
        $paymentData['additional_data'] = array_merge($paymentData, $paymentData['additional']);

        /** @var \Magento\Quote\Model\Quote[] $subQuotes */
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
        $paymentId = $payment['id'];
        $stripeAdapter = $this->adapterFactory->create();
        $cs = $stripeAdapter->customer([
            'email' => $guestEmail ?: $quote->getCustomerEmail(),
            'payment_method' => $paymentId,
            'invoice_settings' => ['default_payment_method' => $paymentId]
        ]);
        $params = [
            StripeEngine::CUSTOMER => $cs->id,
            StripeEngine::AMOUNT => $this->formatPrice($amount),
            StripeEngine::CURRENCY => $currency,
            StripeEngine::PAYMENT_METHOD_TYPES => ['card'],
            StripeEngine::CONFIRMATION_METHOD => 'manual',
            StripeEngine::CAPTURE_METHOD => 'manual',
            StripeEngine::SETUP_FUTURE_USAGE => 'off_session'
        ];
        $params[StripeEngine::PAYMENT_METHOD] = $paymentId;
        $paymentIntent = $stripeAdapter->createPaymentIntent($params);

        $paymentMethod = $paymentIntent->payment_method;
        $paymentData['cc_token'] = $paymentMethod;
        $paymentData['additional_data']['cc_token'] = $paymentMethod;
        $paymentData['additional_data']['customer'] = $cs->id;

        $result = $this->vaultPaymentAuthorization->processPreAuthForTrial($paymentData, $quote, $guestEmail);
        $paymentToken = $result['payment_token'];
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $paymentToken->setCustomerId($quote->getCustomerId());
        $paymentToken->setPaymentMethodCode('tnw_stripe');
        $this->paymentTokenRepository->save($paymentToken);
        /** @var \Magento\Quote\Model\Quote $subQuote */
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
