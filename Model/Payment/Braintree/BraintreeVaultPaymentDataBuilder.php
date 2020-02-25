<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use \Magento\Braintree\Gateway\Request\PaymentDataBuilder;
use \Magento\Payment\Gateway\Command\CommandException;
use \Magento\Braintree\Gateway\Request\SettlementDataBuilder;

class BraintreeVaultPaymentDataBuilder extends BraintreePaymentDataBuilder
{
    public function build($order, $paymentData)
    {
        $payment = $order->getPayment();
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        if ($paymentToken === null) {
            throw new CommandException(__('The Payment Token is not available to perform the request.'));
        }
        $amount = ['amount' => 1]; //TODO: configurable
        $result = [
            'options' => [
                SettlementDataBuilder::SUBMIT_FOR_SETTLEMENT => true
            ],
            PaymentDataBuilder::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($amount)),
            'store_id' => $order->getStoreId(),
            'paymentMethodToken' => $paymentToken->getGatewayToken()
        ];
        $merchantAccountId = $this->braintreeConfig->getMerchantAccountId($order->getStoreId());
        if (!empty($merchantAccountId)) {
            $result[self::$merchantAccountId] = $merchantAccountId;
        }
        return $result;
    }
}
