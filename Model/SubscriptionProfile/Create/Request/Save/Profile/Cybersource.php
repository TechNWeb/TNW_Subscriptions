<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Quote\Model\Quote;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization;
use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile;

/**
 * Class Cybersource -used for process save action for cybersource payed subscriptions
 */
class Cybersource extends Base
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
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;

    /**
     * Cybersource constructor.
     * @param CreateProfile $createModel
     * @param QuoteSessionInterface $session
     * @param VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param EncryptorInterface $encryptor
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenFactory $paymentTokenFactory
     */
    public function __construct(
        CreateProfile $createModel,
        QuoteSessionInterface $session,
        VaultPaymentAuthorization $vaultPaymentAuthorization,
        EncryptorInterface $encryptor,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenFactory $paymentTokenFactory
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->encryptor = $encryptor;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        parent::__construct($createModel, $session);
    }

    /**
     * @param array $data
     * @throws LocalizedException
     * @throws CommandException
     */
    public function process(array $data)
    {
        if (empty($data['payment']['chcybersource']['method'])) {
            return;
        }
        $paymentData = $data['payment']['chcybersource'];
        $paymentData['method'] = 'chcybersource';
        $paymentData['additional_data'] = array_merge($paymentData, $paymentData['additional']);

        /** @var Quote[] $subQuotes */
        $subQuotes = $this->getSubCreateModel()->getSubQuotes();
        $quote = reset($subQuotes);
        $paymentToken = $this->paymentTokenFactory->create('card');
        $paymentToken->setPublicHash($this->generatePublicHash($paymentData, $quote->getCustomerId()));
        $paymentToken->setGatewayToken($paymentData['payment_token']);
        $paymentToken->setCustomerId($quote->getCustomerId());
        $paymentToken->setPaymentMethodCode('chcybersource');
        $paymentToken->setTokenDetails($this->getTokenDetails($paymentData['additional']));
        $paymentToken->setIsActive(true);
        $paymentToken->setIsVisible(true);
        $time = $paymentData['additional']['cc_exp_year']
            . '-' . $paymentData['additional']['cc_exp_month'] . '-01';
        $paymentToken->setExpiresAt(strtotime($time));
        $this->paymentTokenRepository->save($paymentToken);
        /** @var Quote $subQuote */
        foreach ($subQuotes as $subQuote) {
            if ($subQuote->getGrandTotal() < 0.001) {
                $vaultPaymentData = [];
                $vaultPaymentData['method'] = $this->getVaultMethodCode();
                $vaultPaymentData['additional_data']['customer_id'] = $subQuote->getCustomerId();
                $vaultPaymentData['additional_data']['public_hash'] = $paymentToken->getPublicHash();
                $this->vaultPaymentAuthorization->processPreAuthForTrial($vaultPaymentData, $subQuote);
            }
            $subQuote->getPayment()
                ->setAdditionalInformation('cc_number', $paymentData['additional']['cc_number'])
                ->setAdditionalInformation('customer_id', $subQuote->getCustomerId())
                ->setMethod($this->getVaultMethodCode())
                ->setAdditionalInformation('public_hash', $paymentToken->getPublicHash())
                ->setCcType($paymentData['additional']['cc_type'])
                ->setCcLast4(substr($paymentData['additional']['cc_number'], -4))
                ->setCcExpMonth($paymentData['additional']['cc_exp_month'])
                ->setCcExpYear($paymentData['additional']['cc_exp_year']);
        }

        $this->getSubCreateModel()->setNeedCollect(true);
    }

    /**
     * @return string
     */
    public function getVaultMethodCode()
    {
        return 'chcybersource_cc_vault';
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
        $hashKey .= 'chcybersource'
            . 'card'
            . $this->getTokenDetails($paymentData['additional']);

        return $this->encryptor->getHash($hashKey);
    }

    /**
     * @param $paymentData
     * @return false|string
     */
    private function getTokenDetails($paymentData)
    {
        //TODO:: get valid pyament method title
        return json_encode(
            [
                "type" => $paymentData['cc_type'],
                "maskedCC" => "****-****-****-" . substr($paymentData['cc_number'], -4),
                "incrementId" => null,
                "expirationDate" => $paymentData['cc_exp_month'] . "/" . $paymentData['cc_exp_year'],
                "title"=>"CyberSource Stored Cards"
            ]
        );
    }
}
