<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use Magento\Quote\Model\Quote\Payment;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;

/**
 * Class Base
 */
abstract class Base implements EngineInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Context
     */
    private $context;

    /**
     * Base constructor.
     * @param Config $config
     * @param Context $context
     */
    public function __construct(
        Config $config,
        Context $context
    ) {
        $this->config = $config;
        $this->context = $context;
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
     * {@inheritdoc}
     */
    public function processProfile(SubscriptionProfileInterface $profile)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function updateProfile(SubscriptionProfileInterface $profile)
    {

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
}