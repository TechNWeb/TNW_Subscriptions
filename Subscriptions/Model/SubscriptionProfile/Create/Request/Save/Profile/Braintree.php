<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

use Magento\Framework\Exception\PaymentException;

/**
 * Save payment data processor.
 */
class Braintree extends Base
{
    /**
     * @var \Magento\Braintree\Gateway\Http\TransferFactory
     */
    private $transferFactory;

    /**
     * @var \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer
     */
    private $transactionCustomer;

    /**
     * @param \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel
     * @param \TNW\Subscriptions\Model\QuoteSessionInterface $session
     * @param \Magento\Braintree\Gateway\Http\TransferFactory $transferFactory
     * @param \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
     */
    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\CreateProfile $createModel,
        \TNW\Subscriptions\Model\QuoteSessionInterface $session,
        \Magento\Braintree\Gateway\Http\TransferFactory $transferFactory,
        \TNW\Subscriptions\Model\Payment\Braintree\Gateway\Http\Client\TransactionCustomer $transactionCustomer
    ) {
        parent::__construct($createModel, $session);

        $this->transferFactory = $transferFactory;
        $this->transactionCustomer = $transactionCustomer;
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

            $customer = reset($subQuotes)->getCustomer();
            $transfer = $this->transferFactory->create([
                'firstName' => $customer->getFirstname(),
                'lastName' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'paymentMethodNonce' => $data['payment']['braintree']['nonce']
            ]);

            /** @var \Braintree\Result\Error|\Braintree\Result\Successful $response */
            $response = $this->transactionCustomer->placeRequest($transfer);
            if ($response['object'] instanceof \Braintree\Result\Error) {
                $errors = [];
                foreach($response->errors->deepAll() AS $error) {
                    $errors[] = "{$error->code}: {$error->message}";
                }

                throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
            }

            /** @var \Braintree\CreditCard $paymentMethod */
            $paymentMethod = $response['object']->customer->paymentMethods[0];

            /** @var \Magento\Quote\Model\Quote $subQuote */
            foreach ($subQuotes as $subQuote) {
                $subQuote->getPayment()->setAdditionalInformation('payment_method_token', $paymentMethod->token);
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
