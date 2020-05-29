<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save payment processor.
 */
class PaymentMethod extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (isset($data['payment'])) {
            $result = [];
            foreach ($data['payment'] as $code => $methodData) {
                if ($code !== 'undefined' && $methodData['method']) {
                    $result = $this->getSubCreateModel()->setPaymentMethod($code);
                    break;
                }
            }
            $this->errors = $result;
        }
    }
}
