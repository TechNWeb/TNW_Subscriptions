<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout;

/**
 * Interface for config provider.
 */
interface ConfigProviderInterface
{
    /**
     * Get config
     *
     * @return array
     */
    public function getConfig();
}
