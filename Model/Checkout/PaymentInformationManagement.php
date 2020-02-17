<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

/**
 * Class PaymentInformationManagement
 * @package TNW\Subscriptions\Model\Checkout
 */
class PaymentInformationManagement
    extends \Magento\Checkout\Model\PaymentInformationManagement
    implements \TNW\Subscriptions\Api\PaymentInformationManagementInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    protected $vaultPaymentAuthorization;

    public function __construct(
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
    ) {
        parent::__construct(
            $billingAddressManagement,
            $paymentMethodManagement,
            $cartManagement,
            $paymentDetailsFactory,
            $cartTotalsRepository
        );
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Fix: param cartId type
     *
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
            $this->vaultPaymentAuthorization->processPreAuthForTrial(
                $paymentMethod->getData(),
                $this->quoteRepository->get($cartId)
            );
            $this->quoteRepository->get($cartId)->setSubscriptionPaymentDataSet(true);
            $paymentMethod->setMethod('free');
        }

        return parent::savePaymentInformationAndPlaceOrder($cartId, $paymentMethod, $billingAddress);
    }
}
