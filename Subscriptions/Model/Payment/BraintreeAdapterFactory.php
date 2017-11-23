<?php
namespace TNW\Subscriptions\Model\Payment;

use Magento\Braintree\Gateway\Config\Config;
use Magento\Framework\ObjectManagerInterface;

/**
 * This factory is preferable to use for Braintree adapter instance creation.
 */
class BraintreeAdapterFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Config $config
     */
    public function __construct(ObjectManagerInterface $objectManager, Config $config)
    {
        $this->config = $config;
        $this->objectManager = $objectManager;
    }

    /**
     * Creates instance of Braintree Adapter.
     *
     * @return BraintreeAdapter
     */
    public function create()
    {
        return $this->objectManager->create(
            BraintreeAdapter::class,
            [
                'merchantId' => $this->config->getValue(Config::KEY_MERCHANT_ID),
                'publicKey' => $this->config->getValue(Config::KEY_PUBLIC_KEY),
                'privateKey' => $this->config->getValue(Config::KEY_PRIVATE_KEY),
                'environment' => $this->config->getValue(Config::KEY_ENVIRONMENT),
            ]
        );
    }
}
