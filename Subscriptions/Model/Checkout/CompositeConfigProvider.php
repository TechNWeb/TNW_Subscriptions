<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout;

/**
 * Config provider.
 */
class CompositeConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigProviderInterface[]
     */
    private $configProviders;

    /**
     * @param ConfigProviderInterface[] $configProviders
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
        $config[] = [];
        foreach ($this->configProviders as $configProvider) {
            $config[] = $configProvider->getConfig();
        }

        return array_merge_recursive(...$config);
    }
}
