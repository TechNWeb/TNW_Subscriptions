<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Request\TransactionSourceDataBuilder as OriginSourceBuilder;

/**
 * Class PaymentDetailsHandler - adds new transaction sources
 */
class PaymentDetailsHandler
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * PaymentDetailsHandler constructor.
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
        $payment = $this->subjectReader->readPayment($handlingSubject);
        $orderItems = $payment->getOrder()->getItems();
        $subscriptionOrder = false;
        foreach ($orderItems as $item) {
            $productOptions = $item->getData('product_options');
            if ($productOptions
                && is_array($productOptions)
                && array_key_exists('info_buyRequest', $productOptions)
                && array_key_exists('subscribe_active', $productOptions['info_buyRequest'])
                && $productOptions['info_buyRequest']['subscribe_active']
            ) {
                $subscriptionOrder = true;
            }
        }
        if ($subscriptionOrder) {
            if ($payment->getPayment()->getAdditionalInformation('is_rebill')) {
                $source = 'RECURRING';
            } else {
                $source = 'RECURRING_FIRST';
            }
            //TODO: add check against re-bill to the trasaction soruce is recurring
            $payment->getPayment()
                ->setAdditionalInformation(OriginSourceBuilder::TRANSACTION_SOURCE, $source);
        }
    }
}
