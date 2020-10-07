<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class CompositeConfigProvider - dataprovider for checkout
 */
class CompositeConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigProviderInterface[]
     */
    private $configProviders;

    /**
     * @param ConfigProviderInterface[] $configProviders
     * @codeCoverageIgnore
     */
    public function __construct(
        array $configProviders
    ) {
        $this->configProviders = $configProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $configs = array_map([$this, 'configByProvider'], $this->configProviders);
        if (empty($configs)) {
            return [];
        }

        return array_merge_recursive(...array_values($configs));
    }

    /**
     * @param ConfigProviderInterface $provider
     *
     * @return array
     */
    private function configByProvider(ConfigProviderInterface $provider)
    {
        return $provider->getConfig();
    }
}
