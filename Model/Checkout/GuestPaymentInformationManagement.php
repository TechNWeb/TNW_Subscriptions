<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

class GuestPaymentInformationManagement extends \Magento\Checkout\Model\GuestPaymentInformationManagement implements \TNW\Subscriptions\Api\GuestPaymentInformationManagementInterface
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
        // TODO: Hard use Vault
        $additionalData = $paymentMethod->getAdditionalData();
        $additionalData['is_active_payment_token_enabler'] = 1;
        $paymentMethod->setAdditionalData($additionalData);

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

        if ($quote->getBaseGrandTotal() < 0.0001) {
            $quote->setData('subscription_payment_data', json_encode($paymentMethod->getData()));
            $paymentMethod->setMethod('free');
        } else {
            $quote->unsetData('subscription_payment_data');
        }

        return parent::savePaymentInformationAndPlaceOrder($cartId, $email, $paymentMethod, $billingAddress);
    }
}
