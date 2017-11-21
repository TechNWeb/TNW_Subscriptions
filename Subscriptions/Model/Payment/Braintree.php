<?php
namespace TNW\Subscriptions\Model\Payment;

use Magento\Braintree\Gateway\Config\Config;
use Magento\Braintree\Model\Adminhtml\Source\Environment;

class Braintree
{
    /**
     * Braintree constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        if ($config->getValue(Config::KEY_ENVIRONMENT) == Environment::ENVIRONMENT_PRODUCTION) {
            \Braintree\Configuration::environment(Environment::ENVIRONMENT_PRODUCTION);
        } else {
            \Braintree\Configuration::environment(Environment::ENVIRONMENT_SANDBOX);
        }

        \Braintree\Configuration::merchantId($config->getValue(Config::KEY_MERCHANT_ID));
        \Braintree\Configuration::publicKey($config->getValue(Config::KEY_PUBLIC_KEY));
        \Braintree\Configuration::privateKey($config->getValue(Config::KEY_PRIVATE_KEY));
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $nonce
     * @return \Braintree\CreditCard
     * @throws \Exception
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

            throw new \Exception(implode("\n ", $errors));
        }

        return $result->customer->paymentMethods[0];
    }

    /**
     * @param string $token
     * @return string
     * @throws \Exception
     */
    public function generateNonce($token)
    {
        $result = \Braintree\PaymentMethodNonce::create($token);

        if (!$result->success) {
            $errors = [];
            foreach($result->errors->deepAll() AS $error) {
                $errors[] = "{$error->code}: {$error->message}";
            }

            throw new \Exception(implode("\n ", $errors));
        }

        return $result->paymentMethodNonce->nonce;
    }
}