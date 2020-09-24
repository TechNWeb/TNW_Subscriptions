<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Stripe;

use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;

/**
 * Class StripePaymentDataBuilder
 */
class StripePaymentDataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const DESCRIPTION = 'description';
    const CONFIRMATION_METHOD = 'confirmation_method';
    const PAYMENT_METHOD = 'payment_method';
    const PAYMENT_METHOD_TYPES = 'payment_method_types';
    const RECEIPT_EMAIL = 'receipt_email';
    const PI = 'pi';
    const CAPTURE_METHOD = 'capture_method';
    const SHIPPING_ADDRESS = 'shipping';
    const STREET_ADDRESS = 'line1';
    const EXTENDED_ADDRESS = 'line2';
    const LOCALITY = 'city';
    const REGION = 'state';
    const POSTAL_CODE = 'postal_code';
    const COUNTRY_CODE = 'country';
    const NAME = 'name';
    const PHONE = 'phone';
    const SOURCE = 'source';
    const CUSTOMER = 'customer';

    /**
     * @var mixed
     */
    private $config;

    /**
     * @var mixed
     */
    private $adapterFactory;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var mixed
     */
    private $customerClient;

    /**
     * @var mixed
     */
    private $transferFactory;

    /**
     * StripePaymentDataBuilder constructor.
     * @param SubscriptionConfig $subscriptionConfig
     * @param Manager $manager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        SubscriptionConfig $subscriptionConfig,
        Manager $manager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        if ($moduleManager->isEnabled("TNW_Stripe")) {
            $this->config = $objectManager->get("TNW\Stripe\Gateway\Config\Config");
            $this->adapterFactory = $objectManager->get('TNW\Stripe\Model\Adapter\StripeAdapterFactory');
            $this->customerClient = $objectManager->get('TNW\Stripe\Gateway\Http\Client\TransactionCustomer');
            $this->transferFactory = $objectManager->get('TNW\Stripe\Gateway\Http\TransferFactory');
        }
        $this->manager = $manager;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
        $billingAddress = $order->getBillingAddress();
        $result = [
            'store_id' => $order->getStoreId(),
            self::AMOUNT => $this->formatPrice($this->getAmount($order)),
            self::CURRENCY => $order->getCurrencyCode() ? : $order->getQuoteCurrencyCode(),
            self::PAYMENT_METHOD_TYPES => ['card'],
            self::CONFIRMATION_METHOD => 'manual',
            self::CAPTURE_METHOD => 'manual'
        ];

        if ($this->config->isReceiptEmailEnabled()) {
            $result[self::RECEIPT_EMAIL] = $billingAddress->getEmail() ? : $paymentData['customer_guest_email'];
        }
        $addtionalDataToken = isset($paymentData['additional_data']['cc_token'])
            ? $paymentData['additional_data']['cc_token']
            : '';
        $token = isset($paymentData['cc_token'])
            ? $paymentData['cc_token']
            : $addtionalDataToken;
        if ($token) {
            if (strpos($token, 'pi_') !== false) {
                $result[self::PI] = $token;
            } else {
                $result[self::PAYMENT_METHOD] = $token;
            }
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result[self::SHIPPING_ADDRESS] = [
                'address' => [
                    self::STREET_ADDRESS => $shippingAddress->getStreetLine1(),
                    self::EXTENDED_ADDRESS => $shippingAddress->getStreetLine2(),
                    self::LOCALITY => $shippingAddress->getCity(),
                    self::REGION => $shippingAddress->getRegionCode(),
                    self::POSTAL_CODE => $shippingAddress->getPostcode(),
                    self::COUNTRY_CODE => $shippingAddress->getCountryId()
                ],
                self::NAME => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getLastname(),
                self::PHONE => $shippingAddress->getTelephone()
            ];
        }
        if (isset($paymentData['additional_data']['public_hash'])) {
            $paymentToken = $this->paymentTokenManagement->getByPublicHash(
                $paymentData['additional_data']['public_hash'],
                $order->getCustomerId()
            );
            if ($paymentToken) {
                $gateWayToken = $paymentToken->getGatewayToken();
                $result[self::CUSTOMER] = $gateWayToken;
                $stripeAdapter = $this->adapterFactory->create();
                $customer = $stripeAdapter->retrieveCustomer($result[self::CUSTOMER]);
                $pm = $customer->invoice_settings->default_payment_method;
                $result['payment_method'] = $pm;
            }
        } else {
            $customerRequestData = [
                'store_id' => $order->getStoreId()];
            $token = isset($paymentData['additional_data']['cc_token'])
                ? $paymentData['additional_data']['cc_token']
                : '';
            if (strpos($token, 'pm_') !== false) {
                $pm = $token;
            } else {
                $stripeAdapter = $this->adapterFactory->create();
                $paymentIntent = $stripeAdapter->retrievePaymentIntent($token);
                $pm = $paymentIntent->payment_method;
                $cs = $stripeAdapter->retrieveCustomer($paymentIntent->customer);
                if ($cs && $cs->id) {
                    $customerRequestData['id'] = $cs->id;
                }
            }
            $customerRequestData['email'] = $billingAddress->getEmail() ? : $paymentData['customer_guest_email'];
            if (!isset($customerRequestData['id'])) {
                $customerRequestData['payment_method'] = $pm;
            }
            $customerRequestData['invoice_settings'] = ['default_payment_method' => $pm];
            try {
                $this->customerClient->placeRequest($this->transferFactory->create($customerRequestData));
            } catch (\Magento\Payment\Gateway\Http\ClientException $e) {
                $result[self::CUSTOMER] = $paymentData['additional_data']['customer'];
            }
        }

        return $result;
    }

    /**
     * @param $price
     * @return mixed
     */
    public function formatPrice($price)
    {
        $price = sprintf('%.2F', $price);

        return str_replace('.', '', $price);
    }
}
