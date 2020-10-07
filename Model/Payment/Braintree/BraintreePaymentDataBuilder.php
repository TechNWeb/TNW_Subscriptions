<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Payment\Gateway\Config\Config;
use TNW\Subscriptions\Model\Config as SubscriptionConfig;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class BraintreePaymentDataBuilder used for build data for braintree payments
 */
class BraintreePaymentDataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    const CODE_3DSECURE = 'three_d_secure';

    /**
     * Additional data for Advanced Fraud Tools
     */
    const DEVICE_DATA = 'deviceData';

    /**
     * The billing amount of the request. This value must be greater than 0,
     * and must match the currency format of the merchant account.
     */
    const AMOUNT = 'amount';

    /**
     * One-time-use token that references a payment method provided by your customer,
     * such as a credit card or PayPal account.
     *
     * The nonce serves as proof that the user has authorized payment (e.g. credit card number or PayPal details).
     * This should be sent to your server and used with any of Braintree's server-side client libraries
     * that accept new or saved payment details.
     * This can be passed instead of a payment_method_token parameter.
     */
    const PAYMENT_METHOD_NONCE = 'paymentMethodNonce';

    /**
     * The merchant account ID used to create a transaction.
     * Currency is also determined by merchant account ID.
     * If no merchant account ID is specified, Braintree will use your default merchant account.
     */
    const MERCHANT_ACCOUNT_ID = 'merchantAccountId';

    /**
     * Order ID Key
     */
    const ORDER_ID = 'orderId';

    /**
     * Customer block name
     */
    const CUSTOMER = 'customer';

    /**
     * The first name value must be less than or equal to 255 characters.
     */
    const FIRST_NAME = 'firstName';

    /**
     * The last name value must be less than or equal to 255 characters.
     */
    const LAST_NAME = 'lastName';

    /**
     * The customer’s company. 255 character maximum.
     */
    const COMPANY = 'company';

    /**
     * The customer’s email address, comprised of ASCII characters.
     */
    const EMAIL = 'email';

    /**
     * Phone number. Phone must be 10-14 characters and can
     * only contain numbers, dashes, parentheses and periods.
     */
    const PHONE = 'phone';

    /**
     * ShippingAddress block name
     */
    const SHIPPING_ADDRESS = 'shipping';

    /**
     * BillingAddress block name
     */
    const BILLING_ADDRESS = 'billing';

    /**
     * The street address. Maximum 255 characters, and must contain at least 1 digit.
     * Required when AVS rules are configured to require street address.
     */
    const STREET_ADDRESS = 'streetAddress';

    /**
     * The extended address information—such as apartment or suite number. 255 character maximum.
     */
    const EXTENDED_ADDRESS = 'extendedAddress';

    /**
     * The locality/city. 255 character maximum.
     */
    const LOCALITY = 'locality';

    /**
     * The state or province. For PayPal addresses, the region must be a 2-letter abbreviation;
     * for all other payment methods, it must be less than or equal to 255 characters.
     */
    const REGION = 'region';

    /**
     * The postal code. Postal code must be a string of 5 or 9 alphanumeric digits,
     * optionally separated by a dash or a space. Spaces, hyphens,
     * and all other special characters are ignored.
     */
    const POSTAL_CODE = 'postalCode';

    /**
     * The ISO 3166-1 alpha-2 country code specified in an address.
     * The gateway only accepts specific alpha-2 values.
     *
     * @link https://developers.braintreepayments.com/reference/general/countries/php#list-of-countries
     */
    const COUNTRY_CODE = 'countryCodeAlpha2';

    /**
     * Additional options in request to gateway
     */
    const OPTIONS = 'options';

    /**
     * The option that determines whether the payment method associated with
     * the successful transaction should be stored in the Vault.
     */
    const STORE_IN_VAULT_ON_SUCCESS = 'storeInVaultOnSuccess';

    /**
     * Payment method nonce param name
     */
    const DATA_PAYMENT_METHOD_NONCE = 'payment_method_nonce';

    /**
     * Device Data param name
     */
    const DATA_DEVICE_DATA = 'device_data';

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
     * @var mixed
     */
    protected $subjectReader;

    /**
     * @var mixed
     */
    protected $braintreeConfig;

    /**
     * @var mixed
     */
    protected $paymentNonceCommand;

    /**
     * BraintreePaymentDataBuilder constructor.
     * @param ProductMetadataInterface $productMetadata
     * @param SubscriptionConfig $subscriptionConfig
     * @param Manager $manager
     * @param ModuleManager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param Config|null $config
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        SubscriptionConfig $subscriptionConfig,
        Manager $manager,
        ModuleManager $moduleManager,
        ObjectManagerInterface $objectManager,
        Config $config = null
    ) {
        if ($moduleManager->isEnabled("PayPal_Braintree")) {
            $this->braintreeConfig = $objectManager->get(\PayPal\Braintree\Gateway\Config\Config::class);
            $this->subjectReader = $objectManager->get(\PayPal\Braintree\Gateway\Helper\SubjectReader::class);
            $this->paymentNonceCommand = $objectManager->get(
                \PayPal\Braintree\Gateway\Command\GetPaymentNonceCommand::class
            );
        }
        $this->productMetadata = $productMetadata;
        $this->config = $config ?: $objectManager->get(Config::class);
        parent::__construct($subscriptionConfig, $manager);
    }

    /**
     * @param $order
     * @param $paymentData
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function build($order, $paymentData)
    {
        $amount = ['amount' => $this->getAmount($order)];
        $billingAddress = $order->getBillingAddress();
        $channel = $this->config->getValue('channel');

        $paymentAdditionalData = $paymentData['additional_data'];
        if (!array_key_exists(self::DATA_PAYMENT_METHOD_NONCE, $paymentAdditionalData)
            && array_key_exists(PaymentTokenInterface::CUSTOMER_ID, $paymentAdditionalData)
            && array_key_exists(PaymentTokenInterface::PUBLIC_HASH, $paymentAdditionalData)
        ) {
            $paymentData['additional_data'][self::DATA_PAYMENT_METHOD_NONCE] = $this->paymentNonceCommand
                ->execute([
                    PaymentTokenInterface::CUSTOMER_ID => $paymentAdditionalData[PaymentTokenInterface::CUSTOMER_ID],
                    PaymentTokenInterface::PUBLIC_HASH => $paymentAdditionalData[PaymentTokenInterface::PUBLIC_HASH],
                ])->get()['paymentMethodNonce'];
        }
        $result = [
            self::CUSTOMER => [
                self::FIRST_NAME => $billingAddress->getFirstname(),
                self::LAST_NAME => $billingAddress->getLastname(),
                self::COMPANY => $billingAddress->getCompany(),
                self::PHONE => $billingAddress->getTelephone(),
                self::EMAIL => $billingAddress->getEmail(),
            ],
            self::AMOUNT => $this->formatPrice($this->subjectReader->readAmount($amount)),
            self::PAYMENT_METHOD_NONCE => $paymentData['additional_data'][self::DATA_PAYMENT_METHOD_NONCE],
            self::ORDER_ID => $order->getOrderIncrementId(),
            self::$channel => $channel ?: sprintf(self::$channelValue, $this->productMetadata->getEdition()),
            self::OPTIONS => [
                self::STORE_IN_VAULT_ON_SUCCESS => true
            ]
        ];

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $result[self::BILLING_ADDRESS] = [
                self::FIRST_NAME => $billingAddress->getFirstname(),
                self::LAST_NAME => $billingAddress->getLastname(),
                self::COMPANY => $billingAddress->getCompany(),
                self::STREET_ADDRESS => $billingAddress->getStreetLine1(),
                self::EXTENDED_ADDRESS => $billingAddress->getStreetLine2(),
                self::LOCALITY => $billingAddress->getCity(),
                self::REGION => $billingAddress->getRegionCode(),
                self::POSTAL_CODE => $billingAddress->getPostcode(),
                self::COUNTRY_CODE => $billingAddress->getCountryId()
            ];
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result[self::SHIPPING_ADDRESS] = [
                self::FIRST_NAME => $shippingAddress->getFirstname(),
                self::LAST_NAME => $shippingAddress->getLastname(),
                self::COMPANY => $shippingAddress->getCompany(),
                self::STREET_ADDRESS => $shippingAddress->getStreetLine1(),
                self::EXTENDED_ADDRESS => $shippingAddress->getStreetLine2(),
                self::LOCALITY => $shippingAddress->getCity(),
                self::REGION => $shippingAddress->getRegionCode(),
                self::POSTAL_CODE => $shippingAddress->getPostcode(),
                self::COUNTRY_CODE => $shippingAddress->getCountryId()
            ];
        }

        $amount = $this->formatPrice($this->subjectReader->readAmount($amount));

        if ($this->is3DSecureEnabled($order, $amount)) {
            $result['options'][self::CODE_3DSECURE] = ['required' => true];
        }

        if (!$this->braintreeConfig->hasFraudProtection($order->getStoreId())) {
            $data = isset($paymentData['additional_data']) ? $paymentData['additional_data'] : [];

            if (isset($data[self::DATA_DEVICE_DATA])) {
                $result[self::DEVICE_DATA] = $data[self::DATA_DEVICE_DATA];
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
