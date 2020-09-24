<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Class PayflowPro
 */
class PayflowPro extends Base
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
     * PayflowPro constructor.
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Vault\Api\PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        parent::__construct($createModel, $session);
    }

    /**
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function process(array $data)
    {
        if (empty($data['payment'][$this->getPaymentMethodCode()]['method'])
            || empty($data['payment'][$this->getPaymentMethodCode()]['additional'])
        ) {
            return;
        }
        $paymentData = $data['payment'][$this->getPaymentMethodCode()];
        $paymentData['method'] = $this->getPaymentMethodCode();
        $paymentData['additional_information'] = array_merge($paymentData, $paymentData['additional']);
        $paymentData['additional_information']['pnref'] = $this->getSession()->getPnref();
        $this->getSession()->setPnref('');

        /** @var \Magento\Quote\Model\Quote[] $subQuotes */
        $subQuotes = $this->getSubCreateModel()->getSubQuotes();
        $quote = reset($subQuotes);
        $guestEmail = null;
        if (!$quote->getCustomerId()) {
            $guestEmail = $this->getSession()->getCustomerEmail();
        }
        $result = $this->vaultPaymentAuthorization->processPreAuthForTrial($paymentData, $quote, $guestEmail);
        $paymentToken = $result['payment_token'];
        $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));
        $paymentToken->setCustomerId($quote->getCustomerId());
        $paymentToken->setType('card');
        $paymentToken->setPaymentMethodCode($this->getPaymentMethodCode());
        $this->paymentTokenRepository->save($paymentToken);
        /** @var \Magento\Quote\Model\Quote $subQuote */
        foreach ($subQuotes as $subQuote) {
            $subQuote->getPayment()
                ->setAdditionalInformation('cc_number', $paymentData['additional']['cc_last_4'])
                ->setAdditionalInformation('customer_id', $subQuote->getCustomerId())
                ->setMethod($this->getVaultPaymentCode())
                ->setAdditionalInformation('public_hash', $paymentToken->getPublicHash())
                ->setCcType($paymentData['additional']['cc_type'])
                ->setCcLast4($paymentData['additional']['cc_last_4'])
                ->setCcExpMonth($paymentData['additional']['cc_exp_month'])
                ->setCcExpYear($paymentData['additional']['cc_exp_year']);
        }

        $this->getSubCreateModel()->setNeedCollect(true);
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode()
    {
        return 'payflowpro';
    }

    /**
     * @return string
     */
    public function getVaultPaymentCode()
    {
        return 'payflowpro_cc_vault';
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
}
