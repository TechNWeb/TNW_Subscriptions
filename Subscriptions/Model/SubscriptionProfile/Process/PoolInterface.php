<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Process;

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
    public function getProcessors();

    /**
     * Retrieves modifiers instantiated
     *
     * @return ProcessInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \InvalidArgumentException
     */
    public function getProcessorsInstances();
}