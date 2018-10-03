<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

class GuestPaymentInformationManagement extends \Magento\Checkout\Model\GuestPaymentInformationManagement
{
    /**
     * Fix: param cartId and email type
     *
     * @param string $cartId
     * @param string $email
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     *
     * @return int
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        return parent::savePaymentInformationAndPlaceOrder($cartId, $email, $paymentMethod, $billingAddress);
    }
}
