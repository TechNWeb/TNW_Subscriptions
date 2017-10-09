<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Process;

/**
 * Interface ModifierInterface
 */
interface ProcessInterface
{
    /**
     * Processes subscription profile statuses.
     *
     * @param array $ids
     * @return void
     */
    public function process(array $ids);
}