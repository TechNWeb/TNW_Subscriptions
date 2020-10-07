<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save additional payment data processor.
 */
class AdditionalPayment extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $additionalData = [];
        $paymentData = !empty($data['payment']) ? $data['payment'] : [];
        if ($paymentData && empty($paymentData['undefined'])) {
            foreach ($paymentData as $code => $methodData) {
                if ($methodData['method']) {
                    $additionalData = !empty($methodData['additional']) ? $methodData['additional'] : [];
                    $additionalData['method'] = $code;
                    break;
                }
            }
            $this->errors = $this->getSubCreateModel()->setPaymentData($additionalData);
        }
    }
}
