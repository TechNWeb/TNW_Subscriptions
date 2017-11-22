<?php
namespace TNW\Subscriptions\Model\Payment;

use Braintree\Configuration;
use Magento\Braintree\Gateway\Config\Config;
use Magento\Braintree\Model\Adminhtml\Source\Environment;
use \Magento\Framework\Exception\PaymentException;

/**
 * Braintree Adapter
 */
class Braintree
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->initCredentials();
    }

    /**
     * Initializes credentials.
     *
     * @return void
     */
    protected function initCredentials()
    {
        if ($this->config->getValue(Config::KEY_ENVIRONMENT) == Environment::ENVIRONMENT_PRODUCTION) {
            Configuration::environment(Environment::ENVIRONMENT_PRODUCTION);
        } else {
            Configuration::environment(Environment::ENVIRONMENT_SANDBOX);
        }

        Configuration::merchantId($this->config->getValue(Config::KEY_MERCHANT_ID));
        Configuration::publicKey($this->config->getValue(Config::KEY_PUBLIC_KEY));
        Configuration::privateKey($this->config->getValue(Config::KEY_PRIVATE_KEY));
    }

    /**
     * Create a customer, with a payment method
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface|\Magento\Customer\Model\Customer $customer
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

            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
        }

        return $result->customer->paymentMethods[0];
    }

    /**
     * Create a payment method nonce
     *
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

            throw new PaymentException(__('Braintree message: %1', implode(', ', $errors)));
        }

        return $result->paymentMethodNonce->nonce;
    }
}
