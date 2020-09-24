<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Authorizenet;

use \Magento\Payment\Gateway\Config\Config;
use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;

/**
 * Class AuthorizenetPaymentDataBuilder
 */
class AuthorizenetPaymentDataBuilder extends \TNW\Subscriptions\Model\Payment\DataBuilder
{
    use \Magento\Payment\Helper\Formatter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var
     */
    private $subjectReader;

    public function __construct(
        ObjectManager $objectManager,
        SubscriptionConfig $subscriptionConfig,
        Manager $manager,
        Config $config = null
    ) {
        $this->manager = $manager;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->config = $config ? : $objectManager->get(Config::class);
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
            'transaction_request' => [
                'customer' => [
                    'type' => 'individual',
                    'email' => $billingAddress->getEmail() ? : $paymentData['customer_guest_email']
                ],
                'amount' => $this->formatPrice($this->getAmount($order)),
                'currency_code' => $order->getCurrencyCode(),
                'po_number' => $order->getOrderIncrementId(),
                'payment' => [
                    'opaque_data' => [
                        'data_descriptor' => $paymentData['additional_data']['opaqueDescriptor'],
                        'data_value' => $paymentData['additional_data']['opaqueValue']
                    ]
                ]
            ],
            'store_id' => $order->getStoreId()
        ];

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $result['transaction_request']['bill_to'] = [
                'first_name' => $billingAddress->getFirstname(),
                'last_name' => $billingAddress->getLastname(),
                'company' => $billingAddress->getCompany(),
                'address' => $billingAddress->getStreetLine1(),
                'city' => $billingAddress->getCity(),
                'state' => $billingAddress->getRegionCode(),
                'zip' => $billingAddress->getPostcode(),
                'country' => $billingAddress->getCountryId(),
                'phone_number' => $billingAddress->getTelephone(),
                'email' => $billingAddress->getEmail(),
            ];
        }

        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $result['transaction_request']['ship_to'] = [
                'first_name' => $shippingAddress->getFirstname(),
                'last_name' => $shippingAddress->getLastname(),
                'company' => $shippingAddress->getCompany(),
                'address' => $shippingAddress->getStreetLine1(),
                'city' => $shippingAddress->getCity(),
                'state' => $shippingAddress->getRegionCode(),
                'zip' => $shippingAddress->getPostcode(),
                'country' => $shippingAddress->getCountryId(),
            ];
        }

        if (isset($paymentData['additional_data']['ECIFlag']) && isset($paymentData['additional_data']['CAVV'])) {
            $result['transaction_request']['cardholder_authentication'] = [
                'authentication_indicator' => $paymentData['additional_data']['ECIFlag'],
                'cardholder_authentication_value' => $paymentData['additional_data']['CAVV'],
            ];
        }

        return $result;
    }
}
