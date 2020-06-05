<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Class Vault
 * @package TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile
 */
class Vault extends Base
{
    /**
     * @var string
     */
    protected $vaultMethodCode;

    /**
     * @var \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization
     */
    protected $vaultPaymentAuthorization;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentData;

    /**
     * @var string
     */
    protected $paymentInstanceCode;

    /**
     * Vault constructor.
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param string $vaultMethodCode
     * @param string $paymentInstanceCode
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Magento\Payment\Helper\Data $paymentData,
        $vaultMethodCode = 'vault',
        $paymentInstanceCode = ''
    ) {
        $this->paymentInstanceCode = $paymentInstanceCode;
        $this->paymentData = $paymentData;
        $this->vaultMethodCode = $vaultMethodCode;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
        if (empty($data['payment'][$this->vaultMethodCode]['method'])) {
            return;
        }
        $paymentData = $data['payment'][$this->vaultMethodCode];
        /** @var \Magento\Quote\Model\Quote[] $subQuotes */
        $subQuotes = $this->getSubCreateModel()->getSubQuotes();
        $quote = reset($subQuotes);
        $paymentToken = $this->paymentTokenManagement->getByPublicHash(
            $paymentData['additional']['publicHash'],
            $quote->getCustomerId()
        );
        $details = json_decode($paymentToken->getTokenDetails(), true);
        $this->processTokenDetails($details);
        $expirationPeriods = explode('/', $details['expirationDate']);
        /** @var \Magento\Quote\Model\Quote $subQuote */
        foreach ($subQuotes as $subQuote) {
            if ($subQuote->getGrandTotal() < 0.001) {
                $paymentData['method'] = $this->vaultMethodCode;
                $paymentData['additional_data']['customer_id'] = $subQuote->getCustomerId();
                $paymentData['additional_data']['public_hash'] = $paymentData['additional']['publicHash'];
                if ($this->paymentInstanceCode) {
                    $subQuote->getPayment()
                        ->setMethodInstance($this->paymentData->getMethodInstance($this->paymentInstanceCode));
                }
                $this->vaultPaymentAuthorization->processPreAuthForTrial($paymentData, $subQuote);
            }
            $subQuote->getPayment()
                ->setAdditionalInformation('cc_number', $details['maskedCC'])
                ->setAdditionalInformation('customer_id', $subQuote->getCustomerId())
                ->setAdditionalInformation('is_active_payment_token_enabler', 1)
                ->setMethod($this->vaultMethodCode)
                ->setAdditionalInformation('public_hash', $paymentToken->getPublicHash())
                ->setCcType($details['type'])
                ->setCcLast4($details['maskedCC'])
                ->setCcExpMonth($expirationPeriods[0])
                ->setCcExpYear($expirationPeriods[1]);
        }

        $this->getSubCreateModel()->setNeedCollect(true);
    }

    /**
     * @param $details
     * @return $this
     */
    protected function processTokenDetails(&$details)
    {
        if (!array_key_exists('maskedCC', $details)) {
            $details['maskedCC'] = $details['cc_last_4'];
        }
        if (!array_key_exists('type', $details)) {
            $details['type'] = $details['cc_type'];
        }
        if (!array_key_exists('expirationDate', $details)) {
            $details['expirationDate'] = $details['cc_exp_month'] . '/' . $details['cc_exp_year'];
        }
        return $this;
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
