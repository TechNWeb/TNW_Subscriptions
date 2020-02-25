<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Checkout\Model;

/**
 * Class PaymentInformationManagement
 * @package TNW\Subscriptions\Plugin\Checkout\Model
 */
class PaymentInformationManagement
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization
     */
    protected $vaultPaymentAuthorization;

    /**
     * PaymentInformationManagement constructor.
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization $vaultPaymentAuthorization
    ) {
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface $billingAddress
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress
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

        return [$cartId, $paymentMethod, $billingAddress];
    }
}
