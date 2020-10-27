<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Process;

/**
 * Interface ModifierInterface
 */
interface ProcessInterface  extends ErrorInterface
{
    /**
     * Processes data.
     *
     * @param array $data
     * @return void
     */
    public function process(array $data);
}