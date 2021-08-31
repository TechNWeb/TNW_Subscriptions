<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Response;

use PayPal\Braintree\Gateway\Helper\SubjectReader;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Class ThreeDSecureDetailsHandler - adds 3ds verification results to token additional data
 */
class ThreeDSecureDetailsHandler
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * ThreeDSecureDetailsHandler constructor.
     * @param SubjectReader $subjectReader
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        SubjectReader $subjectReader,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param $subject
     * @param $result
     * @param array $handlingSubject
     * @param array $response
     */
    public function afterHandle(
        $subject,
        $result,
        array $handlingSubject,
        array $response
    ) {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        $transaction = $this->subjectReader->readTransaction($response);

        if (empty($transaction->threeDSecureInfo)) {
            return;
        }
        $info = $transaction->threeDSecureInfo;
        if ($payment->getAdditionalInformation('eciFlag') == 'Success'
            && $payment->getExtensionAttributes()
            && $payment->getExtensionAttributes()->getVaultPaymentToken()
        ) {
            $payment->getExtensionAttributes()->getVaultPaymentToken()->setData(
                'liability_shifted',
                $info->liabilityShifted ? 1 : 0
            );
            $payment->getExtensionAttributes()->getVaultPaymentToken()->setData(
                'liability_shift_possible',
                $info->liabilityShifted ? 1 : 0
            );
        }
    }
}
