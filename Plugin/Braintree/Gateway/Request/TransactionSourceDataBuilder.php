<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Braintree\Gateway\Request;

use PayPal\Braintree\Gateway\Helper\SubjectReader;
use PayPal\Braintree\Gateway\Request\TransactionSourceDataBuilder as OriginSourceBuilder;

/**
 * Class TransactionSourceDataBuilder - adds new transaction sources into subscription transactions
 */
class TransactionSourceDataBuilder
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * TransactionSourceDataBuilder constructor.
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
     * @param $buildSubject
     * @return mixed
     */
    public function afterBuild(
        $subject,
        $result,
        $buildSubject
    ) {
        $payment = $this->subjectReader->readPayment($buildSubject);
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
                $result[OriginSourceBuilder::TRANSACTION_SOURCE] = 'recurring';
            } else {
                $result[OriginSourceBuilder::TRANSACTION_SOURCE] = 'recurring_first';
            }
        }
        return $result;
    }
}
