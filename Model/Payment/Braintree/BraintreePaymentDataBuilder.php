<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use \Magento\Braintree\Gateway\Request\PaymentDataBuilder;
use \Magento\Braintree\Gateway\Request\CustomerDataBuilder;
use \Magento\Braintree\Gateway\SubjectReader;
use \Magento\Framework\App\ProductMetadataInterface;
use \Magento\Payment\Gateway\Config\Config;
use \Magento\Braintree\Gateway\Request\AddressDataBuilder;
use \Magento\Braintree\Gateway\Request\VaultDataBuilder;
use \Magento\Braintree\Gateway\Config\Config as BraintreeConfig;
use \Magento\Braintree\Gateway\Request\KountPaymentDataBuilder;
use \Magento\Braintree\Observer\DataAssignObserver;
use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use \Magento\Framework\App\ObjectManager;
use \Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Class BraintreePaymentDataBuilder
 * @package TNW\Subscriptions\Model\Payment\Braintree
 */
class BraintreePaymentDataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    /**
     * @var string
     */
    private static $channel = 'channel';

    /**
     * @var string
     */
    private static $descriptorKey = 'descriptor';

    /**
     * The merchant account ID used to create a transaction.
     * Currency is also determined by merchant account ID.
     * If no merchant account ID is specified, Braintree will use your default merchant account.
     */
    protected static $merchantAccountId = 'merchantAccountId';

    /**
     * @var string
     */
    private static $channelValue = 'Magento2_Cart_%s_BT';

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var BraintreeConfig
     */
    protected $braintreeConfig;

    /**
     * @var \Magento\Braintree\Gateway\Command\GetPaymentNonceCommand
     */
    protected $paymentNonceCommand;

    /**
     * BraintreePaymentDataBuilder constructor.
     * @param SubjectReader $subjectReader
     * @param ProductMetadataInterface $productMetadata
     * @param BraintreeConfig $braintreeConfig
     * @param SubscriptionConfig $subscriptionConfig
     * @param Manager $manager
     * @param \Magento\Braintree\Gateway\Command\GetPaymentNonceCommand $paymentNonceCommand
     * @param Config|null $config
     */
    public function __construct(
       SubjectReader $subjectReader,
       ProductMetadataInterface $productMetadata,
       BraintreeConfig $braintreeConfig,
       SubscriptionConfig $subscriptionConfig,
       Manager $manager,
       \Magento\Braintree\Gateway\Command\GetPaymentNonceCommand $paymentNonceCommand,
       Config $config = null
    ) {
        $this->paymentNonceCommand = $paymentNonceCommand;
        $this->braintreeConfig = $braintreeConfig;
        $this->subjectReader = $subjectReader;
        $this->productMetadata = $productMetadata;
        $this->config = $config ?: ObjectManager::getInstance()->get(Config::class);
        parent::__construct($subscriptionConfig, $manager);
    }

    /**
     * @param $order
     * @param $paymentData
     * @return array
     */
    public function build($order, $paymentData)
    {
        $amount = ['amount' => $this->getAmount($order)];
        $billingAddress = $order->getBillingAddress();
        $channel = $this->config->getValue('channel');

        $paymentAdditionalData = $paymentData['additional_data'];
        if (
            !array_key_exists(DataAssignObserver::PAYMENT_METHOD_NONCE, $paymentAdditionalData)
            && array_key_exists(PaymentTokenInterface::CUSTOMER_ID, $paymentAdditionalData)
            && array_key_exists(PaymentTokenInterface::PUBLIC_HASH, $paymentAdditionalData)
        ) {
            $paymentData['additional_data'][DataAssignObserver::PAYMENT_METHOD_NONCE] = $this->paymentNonceCommand
                ->execute([
                    PaymentTokenInterface::CUSTOMER_ID => $paymentAdditionalData[PaymentTokenInterface::CUSTOMER_ID],
                    PaymentTokenInterface::PUBLIC_HASH => $paymentAdditionalData[PaymentTokenInterface::PUBLIC_HASH],
                ])->get()['paymentMethodNonce'];
        }
        $result = [
            CustomerDataBuilder::CUSTOMER => [
                CustomerDataBuilder::FIRST_NAME => $billingAddress->getFirstname(),
                CustomerDataBuilder::LAST_NAME => $billingAddress->getLastname(),
                CustomerDataBuilder::COMPANY => $billingAddress->getCompany(),
                CustomerDataBuilder::PHONE => $billingAddress->getTelephone(),
                CustomerDataBuilder::EMAIL => $billingAddress->getEmail(),
            ],
            PaymentDataBuilder::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($amount)),
            PaymentDataBuilder::PAYMENT_METHOD_NONCE =>
                $paymentData['additional_data']
                [DataAssignObserver::PAYMENT_METHOD_NONCE],
            PaymentDataBuilder::ORDER_ID => $order->getOrderIncrementId(),
            self::$channel => $channel ?: sprintf(self::$channelValue, $this->productMetadata->getEdition()),
            VaultDataBuilder::OPTIONS => [
                VaultDataBuilder::STORE_IN_VAULT_ON_SUCCESS => true
            ],
            'store_id' => $order->getStoreId()
        ];

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $result[AddressDataBuilder::BILLING_ADDRESS] = [
                AddressDataBuilder::FIRST_NAME => $billingAddress->getFirstname(),
                AddressDataBuilder::LAST_NAME => $billingAddress->getLastname(),
                AddressDataBuilder::COMPANY => $billingAddress->getCompany(),
                AddressDataBuilder::STREET_ADDRESS => $billingAddress->getStreetLine1(),
                AddressDataBuilder::EXTENDED_ADDRESS => $billingAddress->getStreetLine2(),
                AddressDataBuilder::LOCALITY => $billingAddress->getCity(),
                AddressDataBuilder::REGION => $billingAddress->getRegionCode(),
                AddressDataBuilder::POSTAL_CODE => $billingAddress->getPostcode(),
                AddressDataBuilder::COUNTRY_CODE => $billingAddress->getCountryId()
            ];
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result[AddressDataBuilder::SHIPPING_ADDRESS] = [
                AddressDataBuilder::FIRST_NAME => $shippingAddress->getFirstname(),
                AddressDataBuilder::LAST_NAME => $shippingAddress->getLastname(),
                AddressDataBuilder::COMPANY => $shippingAddress->getCompany(),
                AddressDataBuilder::STREET_ADDRESS => $shippingAddress->getStreetLine1(),
                AddressDataBuilder::EXTENDED_ADDRESS => $shippingAddress->getStreetLine2(),
                AddressDataBuilder::LOCALITY => $shippingAddress->getCity(),
                AddressDataBuilder::REGION => $shippingAddress->getRegionCode(),
                AddressDataBuilder::POSTAL_CODE => $shippingAddress->getPostcode(),
                AddressDataBuilder::COUNTRY_CODE => $shippingAddress->getCountryId()
            ];
        }

        $amount = $this->formatPrice($this->subjectReader->readAmount($amount));

        if ($this->is3DSecureEnabled($order, $amount)) {
            $result['options'][BraintreeConfig::CODE_3DSECURE] = ['required' => true];
        }

        if (!$this->braintreeConfig->hasFraudProtection($order->getStoreId())) {
            $data = isset($paymentData['additional_data']) ? $paymentData['additional_data'] : [];

            if (isset($data[DataAssignObserver::DEVICE_DATA])) {
                $result[KountPaymentDataBuilder::DEVICE_DATA] = $data[DataAssignObserver::DEVICE_DATA];
            }
        }

        $values = $this->braintreeConfig->getDynamicDescriptors($order->getStoreId());
        if (!empty($values)) {
            $result[self::$descriptorKey] = $values;
        }

        $merchantAccountId = $this->braintreeConfig->getMerchantAccountId($order->getStoreId());
        if (!empty($merchantAccountId)) {
            $result[self::$merchantAccountId] = $merchantAccountId;
        }

        return $result;

    }

    /**
     * @param $order
     * @param $amount
     * @return bool
     */
    private function is3DSecureEnabled($order, $amount)
    {
        $storeId = $order->getStoreId();
        if (!$this->braintreeConfig->isVerify3DSecure($storeId)
            || $amount < $this->braintreeConfig->getThresholdAmount($storeId)
        ) {
            return false;
        }

        $billingAddress = $order->getBillingAddress();
        $specificCounties = $this->braintreeConfig->get3DSecureSpecificCountries($storeId);
        if (!empty($specificCounties) && !in_array($billingAddress->getCountryId(), $specificCounties)) {
            return false;
        }

        return true;
    }
}
