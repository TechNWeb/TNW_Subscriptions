<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Checkout\Model;

/**
 * Class Cart
 * @package TNW\Subscriptions\Plugin\Checkout\Model
 */
class Cart
{
    /**
     * @param $subject
     * @param $productInfo
     * @param $requestInfo
     * @return array
     */
    public function beforeAddProduct(
        $subject,
        $productInfo,
        $requestInfo
    ) {
        if (isset($requestInfo['subscribe_button'])) {
            $subscribeOptions = json_decode($requestInfo['subscribe_options'], true);
            $billingFrequency['billing_frequency'] = $subscribeOptions['value'];
            $requestInfo = array_merge($requestInfo, $billingFrequency, $subscribeOptions);
        }
        return [$productInfo, $requestInfo];
    }
}
