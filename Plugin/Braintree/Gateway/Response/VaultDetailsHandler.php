<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Response;

use PayPal\Braintree\Gateway\Helper\SubjectReader;
use Braintree\ThreeDSecureInfo;

/**
 * Class VaultDetailsHandler - used to extend the vault token with liability info
 */
class VaultDetailsHandler
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * VaultDetailsHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
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
        $transaction = $this->subjectReader->readTransaction($response);
        $payment = $paymentDO->getPayment();
        if (empty($transaction->threeDSecureInfo)) {
            return;
        }
        /** @var ThreeDSecureInfo $info */
        $info = $transaction->threeDSecureInfo;
        $extensionAttributes = $payment->getExtensionAttributes();
        if ($extensionAttributes !== null) {
            $vaultPaymentToken = $extensionAttributes->getVaultPaymentToken();
            if ($vaultPaymentToken) {
                $vaultPaymentToken->setData('liability_shift_possible', $info->liabilityShiftPossible ? 1 : 0);
                $vaultPaymentToken->setData('liability_shifted', $info->liabilityShifted ? 1 : 0);
            }
        }
    }
}
