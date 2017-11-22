<?php
namespace TNW\Subscriptions\Model\Payment;

use Braintree\Configuration;
use Magento\Braintree\Model\Adminhtml\Source\Environment;
use Magento\Framework\Exception\PaymentException;

/**
 * Braintree Adapter
 */
class BraintreeAdapter
{
    /**
     * @param $merchantId
     * @param $publicKey
     * @param $privateKey
     * @param $environment
     */
    public function __construct($merchantId, $publicKey, $privateKey, $environment)
    {
        if ($environment == Environment::ENVIRONMENT_PRODUCTION) {
            Configuration::environment(Environment::ENVIRONMENT_PRODUCTION);
        } else {
            Configuration::environment(Environment::ENVIRONMENT_SANDBOX);
        }

        Configuration::merchantId($merchantId);
        Configuration::publicKey($publicKey);
        Configuration::privateKey($privateKey);
    }

    /**
     * Create a customer, with a payment method
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface|\Magento\Customer\Model\Customer $customer
     * @param string $nonce
     * @return \Braintree\CreditCard
     * @throws PaymentException
     */
    public function generatePaymentMethod($customer, $nonce)
    {
        try {
            \Braintree\Customer::find("magento_{$customer->getId()}");
            $result = \Braintree\Customer::update("magento_{$customer->getId()}", [
                'firstName' => $customer->getFirstname(),
                'lastName' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'paymentMethodNonce' => $nonce
            ]);
        } catch (\Braintree\Exception\NotFound $e) {
            $result = \Braintree\Customer::create([
                'id' => "magento_{$customer->getId()}",
                'firstName' => $customer->getFirstname(),
                'lastName' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'paymentMethodNonce' => $nonce
            ]);
        }

        if (!$result->success) {
            $errors = [];
            foreach($result->errors->deepAll() AS $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
        }

        return $result->customer->paymentMethods[0];
    }

    /**
     * Create a payment method nonce
     *
     * @param string $token
     * @return string
     * @throws PaymentException
     */
    public function generateNonce($token)
    {
        $result = \Braintree\PaymentMethodNonce::create($token);

        if (!$result->success) {
            $errors = [];
            foreach($result->errors->deepAll() AS $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
        }

        return $result->paymentMethodNonce->nonce;
    }
}
