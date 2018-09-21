<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Payment;

use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\PaymentMethodManagement as NativeManagement;
use Magento\Quote\Model\Quote;

/**
 * Class PaymentMethodManagement
 */
class PaymentMethodManagement extends NativeManagement
{
    /**
     * {@inheritdoc}
     */
    public function set($cartId, PaymentInterface $method)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($cartId);

        $method->setChecks([
            AbstractMethod::CHECK_USE_CHECKOUT,
            AbstractMethod::CHECK_USE_FOR_COUNTRY,
            AbstractMethod::CHECK_USE_FOR_CURRENCY,
            AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
        ]);
        $payment = $quote->getPayment();
        $data = $method->getData();
        $payment->importData($data);

        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod($payment->getMethod());
        } else {
            // check if shipping address is set
            if ($quote->getShippingAddress()->getCountryId() === null) {
                throw new InvalidTransitionException(__('Shipping address is not set'));
            }
            $quote->getShippingAddress()->setPaymentMethod($payment->getMethod());
        }
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
        return $quote->getPayment()->getId();
    }
}
