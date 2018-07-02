<?php
namespace TNW\Subscriptions\Model\Payment\Braintree;

/**
 * Braintree Adapter
 */
class Adapter extends \Magento\Braintree\Model\Adapter\BraintreeAdapter
{
    /**
     * Create a customer, with a payment method
     *
     * @param array $attributes
     * @return \Braintree\Result\Error|\Braintree\Result\Successful
     */
    public function customer(array $attributes)
    {
        return \Braintree\Customer::create($attributes);
    }

    /**
     * Create a payment method nonce
     *
     * @param string $token
     * @return \Braintree\Result\Error|\Braintree\Result\Successful
     */
    public function generateNonce($token)
    {
        return \Braintree\PaymentMethodNonce::create($token);
    }
}
