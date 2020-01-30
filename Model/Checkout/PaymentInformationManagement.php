<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

class PaymentInformationManagement extends \Magento\Checkout\Model\PaymentInformationManagement implements \TNW\Subscriptions\Api\PaymentInformationManagementInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    public function __construct(
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct(
            $billingAddressManagement,
            $paymentMethodManagement,
            $cartManagement,
            $paymentDetailsFactory,
            $cartTotalsRepository
        );
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Fix: param cartId type
     *
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     *
     * @return int
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        // TODO: Hard use Vault
        $additionalData = $paymentMethod->getAdditionalData();
        $additionalData['is_active_payment_token_enabler'] = 1;
        $paymentMethod->setAdditionalData($additionalData);

        if ($this->quoteRepository->get($cartId)->getBaseGrandTotal() < 0.0001) {
            $this->quoteRepository->get($cartId)->setData(
                'subscription_payment_data',
                json_encode($paymentMethod->getData())
            );
            $paymentMethod->setMethod('free');
        } else {
            $this->quoteRepository->get($cartId)->unsetData('subscription_payment_data');
        }

        return parent::savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
    }
}
