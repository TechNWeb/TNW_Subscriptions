<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save account processor.
 */
class Account extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (isset($data['account'])) {
            $email = !empty($data['account']['email']) ? $data['account']['email'] : null;
            $group = !empty($data['account']['group']) ? $data['account']['group'] : null;
            $this->getSession()->setCustomerEmail($email);
            $this->getSession()->setCustomerGroup($group);
        }

        if (!empty($data['shipping_address']) && !empty($data['shipping_info'])) {
            $address = array_merge($data['shipping_address'], $data['shipping_info']);
            $customerAddressId = !empty($data['shipping_address']['customer_address_id'])
                ? $data['shipping_address']['customer_address_id']
                : null;

            $result = $this->getSubCreateModel()->setShippingAddress($address, $customerAddressId);

            if (is_array($result)) {
                $this->errors = $result;
            }
        }
    }
}
