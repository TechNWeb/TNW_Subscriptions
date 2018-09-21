<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Request;

use Magento\Braintree\Gateway\Request\PaymentDataBuilder as DataBuilder;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\Encryption\EncryptorInterface;

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

    public function __construct(
        SubjectReader $subjectReader,
        EncryptorInterface $encryptor
    ) {
        $this->subjectReader = $subjectReader;
        $this->encryptor = $encryptor;
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

        return $result;
    }
}
