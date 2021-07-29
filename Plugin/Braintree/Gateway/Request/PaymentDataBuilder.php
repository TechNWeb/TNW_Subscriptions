<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Request;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use PayPal\Braintree\Gateway\Config\Config;
use PayPal\Braintree\Gateway\Request\PaymentDataBuilder as DataBuilder;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Class PaymentDataBuilder - plugin
 */
class PaymentDataBuilder
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Config
     */
    private $braintreeConfig;

    /**
     * PaymentDataBuilder constructor.
     * @param SubjectReader $subjectReader
     * @param EncryptorInterface $encryptor
     * @param Config $braintreeConfig
     */
    public function __construct(
        SubjectReader $subjectReader,
        EncryptorInterface $encryptor,
        Config $braintreeConfig
    ) {
        $this->subjectReader = $subjectReader;
        $this->encryptor = $encryptor;
        $this->braintreeConfig = $braintreeConfig;
    }

    /**
     * @param DataBuilder $subject
     * @param callable $callback
     * @param array $buildSubject
     * @return array
     */
    public function aroundBuild(
        DataBuilder $subject,
        callable $callback,
        array $buildSubject
    ) {
        $result = $callback($buildSubject);

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $tokenHash = $payment->getAdditionalInformation('token_hash');
        if (!empty($tokenHash)) {
            $result['paymentMethodToken'] = $this->encryptor->decrypt($tokenHash);
            unset($result[DataBuilder::PAYMENT_METHOD_NONCE]);
            $payment->unsAdditionalInformation('token_hash');
        }
        try {
            $storeId = $paymentDO->getOrder()->getStoreId() ?? null;
            $merchantAccountId = $this->braintreeConfig->getMerchantAccountId($storeId);
        } catch (InputException | NoSuchEntityException $exception) {
            $merchantAccountId = null;
        }
        if (!empty($merchantAccountId)) {
            $result[DataBuilder::MERCHANT_ACCOUNT_ID] = $merchantAccountId;
        }

        return $result;
    }
}
