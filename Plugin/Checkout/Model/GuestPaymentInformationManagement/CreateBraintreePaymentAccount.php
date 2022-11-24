<?php
/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\Model\GuestPaymentInformationManagement;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\PaymentException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use TNW\Subscriptions\Model\Payment\Braintree\ProcessPaymentInformation;

/**
 * Plugin creates Braintree payment account for free trial subscriptions for guest customers.
 */
class CreateBraintreePaymentAccount
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var ProcessPaymentInformation
     */
    private $processPaymentInformation;

    /**
     * CreateBraintreePaymentAccount constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ProcessPaymentInformation $processPaymentInformation
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ProcessPaymentInformation $processPaymentInformation
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->processPaymentInformation = $processPaymentInformation;
    }

    /**
     * Executes before savePaymentInformationAndPlaceOrder instance method.
     *
     * @param GuestPaymentInformationManagementInterface $subject
     * @param mixed $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface $billingAddress
     * @return array|null
     * @throws ClientException
     * @throws ConverterException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws PaymentException
     * @throws InputException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress
    ) {
        if ($paymentMethod->getMethod() !== 'braintree') {
            return null;
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());
        $this->processPaymentInformation->createBraintreePaymentAccountAndSavePaymentToken(
            $quote,
            $paymentMethod,
            $billingAddress
        );

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}
