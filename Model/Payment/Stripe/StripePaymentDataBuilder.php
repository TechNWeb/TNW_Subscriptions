<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Stripe;

use \Magento\Payment\Gateway\Config\Config;
use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use \Magento\Framework\App\ObjectManager;

/**
 * Class StripePaymentDataBuilder
 * @package TNW\Subscriptions\Model\Payment\Stripe
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

    /**
     * @var Config
     */
    private $config;

    /**
     * @var
     */
    private $subjectReader;

    /**
     * StripePaymentDataBuilder constructor.
     * @param SubscriptionConfig $subscriptionConfig
     * @param Manager $manager
     * @param Config|null $config
     */
    public function __construct(
       SubscriptionConfig $subscriptionConfig,
       Manager $manager,
       Config $config = null
    ) {
        $this->manager = $manager;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->config = $config ?: ObjectManager::getInstance()->get(Config::class);
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
            self::AMOUNT => $this->formatPrice($this->getAmount($order)),
            self::CURRENCY => $order->getCurrencyCode(),
            self::PAYMENT_METHOD_TYPES => ['card'],
            self::CONFIRMATION_METHOD => 'manual',
            self::CAPTURE_METHOD => 'manual'
        ];

        if ($this->config->isReceiptEmailEnabled()) {
            $result[self::RECEIPT_EMAIL] = $billingAddress->getEmail() ? : $paymentData['customer_guest_email'];
        }

        if ($token = $paymentData['cc_token']) {
            if (strpos($token, 'pi_') !== false) {
                $result[self::PI] = $token;
            } else {
                $result[self::PAYMENT_METHOD] = $token;
            }
        }

        return $result;
    }

    public function formatPrice($price)
    {
        $price = sprintf('%.2F', $price);

        return str_replace('.', '', $price);
    }
}
