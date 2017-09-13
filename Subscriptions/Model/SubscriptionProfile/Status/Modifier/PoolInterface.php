<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

/**
 * Interface PoolInterface
 */
interface PoolInterface
{
    /**
     * Retrieves modifiers
     *
     * @return array
     */
    public function getModifiers();

    /**
     * Retrieves modifiers instantiated
     *
     * @return ModifierInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \InvalidArgumentException
     */
    public function getModifiersInstances();
}