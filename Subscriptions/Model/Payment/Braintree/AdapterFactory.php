<?php
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Magento\Braintree\Gateway\Config\Config;
use Magento\Braintree\Model\Adapter\BraintreeAdapterFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * This factory is preferable to use for Braintree adapter instance creation.
 */
class AdapterFactory extends BraintreeAdapterFactory
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
        parent::__construct($objectManager, $config);

        $this->config = $config;
        $this->objectManager = $objectManager;
    }

    /**
     * Creates instance of Braintree Adapter.
     *
     * @param null $storeId
     * @return Adapter
     */
    public function create($storeId = null)
    {
        return $this->objectManager->create(
            Adapter::class,
            [
                'merchantId' => $this->config->getValue(Config::KEY_MERCHANT_ID, $storeId),
                'publicKey' => $this->config->getValue(Config::KEY_PUBLIC_KEY, $storeId),
                'privateKey' => $this->config->getValue(Config::KEY_PRIVATE_KEY, $storeId),
                'environment' => $this->config->getValue(Config::KEY_ENVIRONMENT, $storeId),
            ]
        );
    }
}
