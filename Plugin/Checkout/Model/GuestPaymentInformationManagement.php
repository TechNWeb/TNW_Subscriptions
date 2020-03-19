<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\Model;

/**
 * Class GuestPaymentInformationManagement
 * @package TNW\Subscriptions\Plugin\Checkout\Model
 */
class GuestPaymentInformationManagement
{
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization
     */
    private $vaultPaymentAuthorization;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * GuestPaymentInformationManagement constructor.
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     */
    public function __construct(
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
    }

    /**
     * @param $subject
     * @param $cartId
     * @param $email
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress
    ) {
        // TODO: Hard use Vault
        $additionalData = $paymentMethod->getAdditionalData();
        $additionalData['is_active_payment_token_enabler'] = 1;
        $paymentMethod->setAdditionalData($additionalData);

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

        if (
            $quote->getBaseGrandTotal() < 0.0001
            && $paymentMethod->getMethod() != 'chcybersource'
        ) {
            $this->vaultPaymentAuthorization->processPreAuthForTrial(
                $paymentMethod->getData(),
                $quote
            );
            $quote->setSubscriptionPaymentDataSet(true);
            $paymentMethod->setMethod('free');

        }
        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}
