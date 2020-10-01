<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\Model;

/**
 * Class Cart - plugin
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
            if (is_object($requestInfo)) {
                $requestInfo = array_merge($requestInfo->getData(), $billingFrequency, $subscribeOptions);
            } else {
                $requestInfo = array_merge($requestInfo, $billingFrequency, $subscribeOptions);
            }
        }
        return [$productInfo, $requestInfo];
    }
}
