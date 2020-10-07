<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Process;

/**
 * Response process Interface
 */
interface ResponseInterface
{
    /**
     * Processes data.
     *
     * @param array $data
     * @return array
     */
    public function process(array $data);
}
