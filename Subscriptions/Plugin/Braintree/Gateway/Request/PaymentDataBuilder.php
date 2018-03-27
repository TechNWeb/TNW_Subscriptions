<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Request;

use Magento\Braintree\Gateway\Request\PaymentDataBuilder as DataBuilder;
use Magento\Braintree\Gateway\SubjectReader;

class PaymentDataBuilder
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
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

        $token = $payment->getAdditionalInformation('payment_method_token');
        if (!empty($token)) {
            $result['paymentMethodToken'] = $token;
            unset($result[DataBuilder::PAYMENT_METHOD_NONCE]);
            $payment->unsAdditionalInformation('payment_method_token');
        }

        return $result;
    }
}
