<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Request\Save\Profile;

/**
 * Save payment processor.
 */
class Payment extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (isset($data['payment'])) {
            $result = [];
            foreach ($data['payment'] as $code => $methodData) {
                if ($methodData['method']) {
                    $result = $this->getSubCreateModel()->setPaymentMethod($code);
                    break;
                }
            }
            $this->errors = $result;
        }
    }
}