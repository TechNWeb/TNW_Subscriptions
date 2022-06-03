<?php
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Exception;
use Magento\Store\Api\Data\WebsiteInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Request\Http as RequestHttp;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Braintree Adapter - braintree
 */
class Adapter
{
    /**
     * @var mixed
     */
    private $config;

    /**
     * @var mixed
     */
    private $storeConfigResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var null
     */
    private $storeId;

    /**
     * Adapter constructor.
     * @param LoggerInterface $logger
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param RequestHttp $http
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param null $storeId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        LoggerInterface $logger,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        RequestHttp $http,
        SubscriptionProfileRepositoryInterface $profileRepository,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        $storeId = null
    ) {
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->config = $objectManager->get(\PayPal\Braintree\Gateway\Config\Config::class);
            $this->storeConfigResolver = $objectManager->get(\PayPal\Braintree\Model\StoreConfigResolver::class);
        }
        $this->storeId = $storeId;
        $this->logger = $logger;
        $this->http = $http;
        $this->profileRepository = $profileRepository;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        $this->initCredentials();
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param mixed $storeConfigResolver
     */
    public function setStoreConfigResolver($storeConfigResolver)
    {
        $this->storeConfigResolver = $storeConfigResolver;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $attributes
     * @return |null
     */
    public function customer(array $attributes)
    {
        if (class_exists('Braintree\Customer')) {
            return \Braintree\Customer::create($attributes);
        }
        return null;
    }

    /**
     * @param $token
     * @return |null
     */
    public function generateNonce($token)
    {
        if (class_exists('Braintree\PaymentMethodNonce')) {
            return \Braintree\PaymentMethodNonce::create($token);
        }
        return null;
    }

    /**
     * Initialize credentials
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function initCredentials()
    {
        if ($this->storeConfigResolver) {
            $storeId = $this->storeConfigResolver->getStoreId();
            $environmentIdentifier = $this->config->getValue($this->config::KEY_ENVIRONMENT, $storeId);

            $subscriptionProfileId = $this->http->getParam('subscription_profile_id');
            $websiteId = null;
            if ($subscriptionProfileId) {
                $websiteId = $this->profileRepository->getById($subscriptionProfileId)->getWebsiteId();
            }
            if (!$websiteId && $this->storeId) {
                $websiteId = $this->storeManager->getStore($this->storeId)->getWebsiteId();
            }
            $this->environment(Environment::ENVIRONMENT_SANDBOX);

            if ($environmentIdentifier === Environment::ENVIRONMENT_PRODUCTION) {
                $this->environment(Environment::ENVIRONMENT_PRODUCTION);
            }

            if ($websiteId) {
                $this->merchantId(
                    $this->getValueByWebsiteId(
                        $this->config::KEY_MERCHANT_ID,
                        $websiteId,
                        $environmentIdentifier
                    )
                );
                $this->publicKey(
                    $this->getValueByWebsiteId(
                        $this->config::KEY_PUBLIC_KEY,
                        $websiteId,
                        $environmentIdentifier
                    )
                );
                $this->privateKey(
                    $this->getValueByWebsiteId(
                        $this->config::KEY_PRIVATE_KEY,
                        $websiteId,
                        $environmentIdentifier
                    )
                );
            } else {
                $this->merchantId(
                    $this->config->getValue($this->config::KEY_MERCHANT_ID, $storeId)
                );
                $this->publicKey(
                    $this->config->getValue($this->config::KEY_PUBLIC_KEY, $storeId)
                );
                $this->privateKey(
                    $this->config->getValue($this->config::KEY_PRIVATE_KEY, $storeId)
                );
            }

            if ($environmentIdentifier === Environment::ENVIRONMENT_SANDBOX) {
                $configClass = get_class($this->config);
                if ($websiteId) {
                    if (defined($configClass . '::KEY_SANDBOX_MERCHANT_ID')) {
                        $this->merchantId(
                            $this->getValueByWebsiteId(
                                $this->config::KEY_MERCHANT_ID,
                                $websiteId,
                                $environmentIdentifier
                            )
                        );
                    }
                    if (defined($configClass . '::KEY_SANDBOX_PUBLIC_KEY')) {
                        $this->publicKey(
                            $this->getValueByWebsiteId(
                                $this->config::KEY_PUBLIC_KEY,
                                $websiteId,
                                $environmentIdentifier
                            )
                        );
                    }
                    if (defined($configClass . '::KEY_SANDBOX_PRIVATE_KEY')) {
                        $this->privateKey(
                            $this->getValueByWebsiteId(
                                $this->config::KEY_PRIVATE_KEY,
                                $websiteId,
                                $environmentIdentifier
                            )
                        );
                    }
                } else {
                    if (defined($configClass . '::KEY_SANDBOX_MERCHANT_ID')) {
                        $this->merchantId(
                            $this->config->getValue($this->config::KEY_SANDBOX_MERCHANT_ID, $storeId)
                        );
                    }
                    if (defined($configClass . '::KEY_SANDBOX_PUBLIC_KEY')) {
                        $this->publicKey(
                            $this->config->getValue($this->config::KEY_SANDBOX_PUBLIC_KEY, $storeId)
                        );
                    }
                    if (defined($configClass . '::KEY_SANDBOX_PRIVATE_KEY')) {
                        $this->privateKey(
                            $this->config->getValue($this->config::KEY_SANDBOX_PRIVATE_KEY, $storeId)
                        );
                    }
                }
            }
        }
    }

    /**
     * @param string|null $value
     * @return mixed
     */
    public function environment($value = null)
    {
        if (class_exists('Braintree\Configuration')) {
            return \Braintree\Configuration::environment($value);
        }
        return null;
    }

    /**
     * @param string|null $value
     * @return mixed
     */
    public function merchantId($value = null)
    {
        if (class_exists('Braintree\Configuration')) {
            return \Braintree\Configuration::merchantId($value);
        }
        return null;
    }

    /**
     * @param string|null $value
     * @return mixed
     */
    public function publicKey($value = null)
    {
        if (class_exists('Braintree\Configuration')) {
            return \Braintree\Configuration::publicKey($value);
        }
        return null;
    }

    /**
     * @param string|null $value
     * @return mixed
     */
    public function privateKey($value = null)
    {
        if (class_exists('Braintree\Configuration')) {
            return \Braintree\Configuration::privateKey($value);
        }
        return null;
    }

    /**
     * @param array $params
     * @return string
     */
    public function generate(array $params = [])
    {
        try {
            if (class_exists('Braintree\ClientToken')) {
                return \Braintree\ClientToken::generate($params);
            }
            return '';
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return '';
        }
    }

    /**
     * @param string $token
     * @return mixed
     */
    public function find($token)
    {
        try {
            if (class_exists('Braintree\CreditCard')) {
                return \Braintree\CreditCard::find($token);
            }
            return null;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    /**
     * @param array $filters
     * @return |null
     */
    public function search(array $filters)
    {
        if (class_exists('Braintree\Transaction')) {
            return \Braintree\Transaction::search($filters);
        }
        return null;
    }

    /**
     * @param string $id
     * @return |null
     */
    public function findById(string $id)
    {
        if (class_exists('Braintree\Transaction')) {
            return \Braintree\Transaction::find($id);
        }
        return null;
    }

    /**
     * @param $token
     * @return |null
     */
    public function createNonce($token)
    {
        if (class_exists('Braintree\PaymentMethodNonce')) {
            return \Braintree\PaymentMethodNonce::create($token);
        }
        return null;
    }

    /**
     * @param array $attributes
     * @return |null
     */
    public function sale(array $attributes)
    {
        if (class_exists('Braintree\Transaction')) {
            return \Braintree\Transaction::sale($attributes);
        }
        return null;
    }

    /**
     * @param $transactionId
     * @param null $amount
     * @return |null
     */
    public function submitForSettlement($transactionId, $amount = null)
    {
        if (class_exists('Braintree\Transaction')) {
            return \Braintree\Transaction::submitForSettlement($transactionId, $amount);
        }
        return null;
    }

    /**
     * @param $transactionId
     * @return |null
     */
    public function void($transactionId)
    {
        if (class_exists('Braintree\Transaction')) {
            return \Braintree\Transaction::void($transactionId);
        }
        return null;
    }

    /**
     * @param $transactionId
     * @param null $amount
     * @return |null
     */
    public function refund($transactionId, $amount = null)
    {
        if (class_exists('Braintree\Transaction')) {
            return \Braintree\Transaction::refund($transactionId, $amount);
        }
        return null;
    }

    /**
     * Clone original transaction
     * @param string $transactionId
     * @param array $attributes
     * @return mixed
     */
    public function cloneTransaction($transactionId, array $attributes)
    {
        if (class_exists('Braintree\Transaction')) {
            return \Braintree\Transaction::cloneTransaction($transactionId, $attributes);
        }
        return null;
    }

    /**
     * @param $token
     * @return mixed
     */
    public function deletePaymentMethod($token)
    {
        if (class_exists('Braintree\PaymentMethod')) {
            return \Braintree\PaymentMethod::delete($token)->success;
        }
        return null;
    }

    /**
     * @param $token
     * @param $attribs
     * @return mixed
     */
    public function updatePaymentMethod($token, $attribs)
    {
        if (class_exists('Braintree\PaymentMethod')) {
            return \Braintree\PaymentMethod::update($token, $attribs);
        }
        return null;
    }

    /**
     * @param $id
     * @return |null
     */
    public function getCustomerById($id)
    {
        if (class_exists('Braintree\Customer')) {
            return \Braintree\Customer::find($id);
        }
        return null;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getValueByWebsiteId($field, $websiteId, $environmentIdentifier)
    {
        if ($environmentIdentifier === Environment::ENVIRONMENT_SANDBOX) {
            $result = $this->scopeConfig->getValue(
                'payment/braintree/sandbox_' . $field,
                ScopeInterface::SCOPE_WEBSITES,
                $this->getWebsite($websiteId)->getCode()
            );
        } else {
            $result = $this->scopeConfig->getValue(
                'payment/braintree/' . $field,
                ScopeInterface::SCOPE_WEBSITES,
                $websiteId
            );
        }
        return $result;
    }

    /**
     * Returns website by id.
     *
     * @param int|string $websiteId
     * @return WebsiteInterface
     */
    public function getWebsite($websiteId)
    {
        return $this->storeManager->getWebsite($websiteId);
    }
}
