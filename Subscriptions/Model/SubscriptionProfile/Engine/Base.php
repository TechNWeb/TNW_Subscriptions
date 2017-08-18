<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;

/**
 * Class Base
 */
class Base implements EngineInterface
{
    /**
     * Config model.
     *
     * @var Config
     */
    private $config;

    /**
     * @var Context
     */
    private $context;

    /**
     * Subscription profile.
     *
     * @var SubscriptionProfileInterface
     */
    private $profile;

    /**
     * Cart management.
     *
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * Base constructor.
     * @param Config $config
     * @param Context $context
     * @param CartManagementInterface $cartManagement
     */
    public function __construct(
        Config $config,
        Context $context,
        CartManagementInterface $cartManagement
    ) {
        $this->config = $config;
        $this->context = $context;
        $this->cartManagement = $cartManagement;
    }

    /**
     * Returns config object.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns context object.
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns cart management object.
     *
     * @return CartManagementInterface
     */
    public function getCartManagement()
    {
        return $this->cartManagement;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * {@inheritdoc}
     */
    public function setProfile(SubscriptionProfileInterface $profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePaymentInfo(Payment $payment)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentAdditionalInfo(SubscriptionProfileInterface $profile)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function processProfile(Quote $quote)
    {
        try {
            $quote->getPayment()->importData(
                $this->getPaymentInfo($this->getProfile())
            );
            $quote->getPayment()->setAdditionalInformation(
                $this->getPaymentAdditionalInfo($this->getProfile())
            );
            return $this->getCartManagement()->submit($quote);
        } catch (\Exception $e) {
            $quote->setReservedOrderId(null);
            $quote->save();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentInfo(SubscriptionProfileInterface $profile)
    {
        return [];
    }
}