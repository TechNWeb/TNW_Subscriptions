<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Stripe\Gateway\Command;

use TNW\Stripe\Gateway\Helper\SubjectReader;

/**
 * Class StrategyCommand - used to forcibly save card for stripe
 */
class StrategyCommand
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * StrategyCommand constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param $subject
     * @param array $commandSubject
     * @return array
     */
    public function beforeExecute($subject, array $commandSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($commandSubject);
        $payment = $paymentDO->getPayment();
        if (!$payment->getAdditionalInformation('is_active_payment_token_enabler')) {
            $order = $payment->getOrder();
            $isSubscriptionOrder = false;
            foreach ($order->getItems() as $item) {
                $productOptions = $item->getData('product_options');
                if ($productOptions
                    && is_array($productOptions)
                    && array_key_exists('info_buyRequest', $productOptions)
                    && !$isSubscriptionOrder
                ) {
                    $isSubscriptionOrder = array_key_exists(
                        'subscribe_active',
                        $productOptions['info_buyRequest']
                    ) ? $productOptions['info_buyRequest']['subscribe_active']
                        : false;
                }
            }
            if ($isSubscriptionOrder) {
                $payment->setAdditionalInformation('is_active_payment_token_enabler', true);
            }
        }
        return [$commandSubject];
    }
}
