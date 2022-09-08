<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use TNW\Subscriptions\Model\Payment\VaultPaymentAuthorization;

/**
 * Class PaymentInformationManagement - plugin to before process payment info
 */
class PaymentInformationManagement
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var VaultPaymentAuthorization
     */
    protected $vaultPaymentAuthorization;

    /**
     * PaymentInformationManagement constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param VaultPaymentAuthorization $vaultPaymentAuthorization
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        VaultPaymentAuthorization $vaultPaymentAuthorization
    ) {
        $this->vaultPaymentAuthorization = $vaultPaymentAuthorization;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param $subject
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface $billingAddress
     * @return array
     * @throws NoSuchEntityException
     * @throws CommandException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress
    ) {
        $quote = $this->quoteRepository->get($cartId);
        $additionalData = $paymentMethod->getAdditionalData();
        $isSubscription = false;
        foreach ($quote->getItems() as $item) {
            $options = $item->getBuyRequest();
            if (!isset($options['subscribe_active']) || !$options['subscribe_active']) {
                continue;
            }
            $isSubscription = true;
        }
        if ($quote->getData('is_tnw_subscription') || $isSubscription) {
            $additionalData['is_active_payment_token_enabler'] = 1;
            $paymentMethod->setAdditionalData($additionalData);

            if ($quote->getBaseGrandTotal() < 0.0001
            ) {
                $this->vaultPaymentAuthorization->processPreAuthForTrial(
                    $paymentMethod->getData(),
                    $this->quoteRepository->get($cartId)
                );
                $this->quoteRepository->get($cartId)->setSubscriptionPaymentDataSet(true);
                $paymentMethod->setMethod('free');
            }
        }

        return [$cartId, $paymentMethod, $billingAddress];
    }
}
