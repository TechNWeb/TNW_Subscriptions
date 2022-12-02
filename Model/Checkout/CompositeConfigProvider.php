<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use TNW\Subscriptions\Model\Config;

/**
 * Class CompositeConfigProvider - dataprovider for checkout
 */
class CompositeConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     * @codeCoverageIgnore
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $configs = [
            'isSubscriptionEnabled' => (bool) $this->config->isSubscriptionsActiveCurrent(),
            'staticAuthAmount' => $this->config->getStaticAuthAmount(),
        ];

        return $configs;
    }
}
