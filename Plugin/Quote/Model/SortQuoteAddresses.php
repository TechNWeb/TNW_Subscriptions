<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Model;

/**
 * Class SortQuoteAddresses - fixes core issue - https://github.com/magento/magento2/issues/26209
 */
class SortQuoteAddresses
{
    /**
     * @param $subject
     * @param $result
     * @return array
     */
    public function afterGetAllAddresses($subject, $result)
    {
        foreach ($result as $address) {
            if ($address->getAddressType() == 'shipping') {
                $shippingAddresses[] = $address;
            } else {
                $billingAddress = $address;
            }
        }
        if (isset($billingAddress) && isset($shippingAddresses) && is_array($shippingAddresses)) {
            array_unshift($shippingAddresses, $billingAddress);
        } else {
            return $result;
        }
        return $shippingAddresses;
    }
}
