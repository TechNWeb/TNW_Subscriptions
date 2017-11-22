<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Save payment data processor.
 */
class Braintree extends Base
{
    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree
     */
    private $braintree;

    /**
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \TNW\Subscriptions\Model\Payment\Braintree $braintree
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \TNW\Subscriptions\Model\Payment\Braintree $braintree
    ) {
        parent::__construct($createModel, $session);
        $this->braintree = $braintree;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        if (empty($data['payment']['braintree']['method'])) {
            return;
        }

        try {
            /** @var \Magento\Quote\Model\Quote[] $subQuotes */
            $subQuotes = $this->getSubCreateModel()->getSubQuotes();

            /** @var \Braintree\CreditCard $paymentMethod */
            $paymentMethod = $this->braintree->generatePaymentMethod(
                reset($subQuotes)->getCustomer(),
                $data['payment']['braintree']['nonce']
            );

            /** @var \Magento\Quote\Model\Quote $subQuote */
            foreach ($subQuotes as $subQuote) {
                $subQuote->getPayment()
                    ->setAdditionalInformation(
                        'payment_method_nonce',
                        $this->braintree->generateNonce($paymentMethod->token)
                    );

                $subQuote->getPayment()->setAdditionalInformation('payment_token', $paymentMethod->token);
                $subQuote->getPayment()->setCcType($data['payment']['braintree']['additional']['cc_type']);
                $subQuote->getPayment()->setCcLast4($paymentMethod->last4);
                $subQuote->getPayment()->setCcExpMonth($paymentMethod->expirationMonth);
                $subQuote->getPayment()->setCcExpYear($paymentMethod->expirationYear);
            }

            $this->getSubCreateModel()->setNeedCollect(true);
        } catch (\Exception $e) {
            $this->errors[] = __('Payment: %1', $e->getMessage());
        }
    }
}
