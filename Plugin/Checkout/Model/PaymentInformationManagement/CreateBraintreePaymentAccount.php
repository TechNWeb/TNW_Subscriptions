<?php
/**
 * Copyright © TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\Model\PaymentInformationManagement;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\PaymentException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use TNW\Subscriptions\Model\Payment\Braintree\ProcessPaymentInformation;

/**
 * Plugin creates Braintree payment account for free trial subscriptions for registered customers.
 */
class CreateBraintreePaymentAccount
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ProcessPaymentInformation
     */
    private $processPaymentInformation;

    /**
     * CreateBraintreePaymentAccount constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param ProcessPaymentInformation $processPaymentInformation
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProcessPaymentInformation $processPaymentInformation
    ) {
        $this->cartRepository = $cartRepository;
        $this->processPaymentInformation = $processPaymentInformation;
    }

    /**
     * Executes before savePaymentInformationAndPlaceOrder instance method.
     *
     * @param PaymentInformationManagementInterface $subject
     * @param int $cartId
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
        PaymentInformationManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress
    ) {
        if ($paymentMethod->getMethod() !== 'braintree') {
            return null;
        }
        $quote = $this->cartRepository->get($cartId);
        $this->processPaymentInformation->createBraintreePaymentAccountAndSavePaymentToken(
            $quote,
            $paymentMethod,
            $billingAddress
        );
        return [$cartId, $paymentMethod, $billingAddress];
    }
}
