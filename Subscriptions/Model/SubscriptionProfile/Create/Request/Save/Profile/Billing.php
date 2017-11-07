<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save billing address processor.
 */
class Billing extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $address = isset($data['billing_address']) ? $data['billing_address'] : [];
        $info = isset($data['billing_info']) ? $data['billing_info'] : [];
        $billing = array_merge($address, $info);
        if (!empty($billing)) {
            $customerAddressId = !empty($billing['customer_address_id'])
                ? $billing['customer_address_id']
                : null;

            $this->errors = $this->getSubCreateModel()
                ->setBillingAddress($billing, $customerAddressId);

            // Save billing address "Save As Shipping" flag state.
            if (isset($info['same_as_shipping'])) {
                $quoteAddressData = $this->getSession()->getQuoteAddressData() ?: [];
                $quoteAddressData[$this->getSession()->getFirstQuote()->getId()] = $info['same_as_shipping'];
                $this->getSession()->setQuoteAddressData($quoteAddressData);
            }
        }
    }
}
