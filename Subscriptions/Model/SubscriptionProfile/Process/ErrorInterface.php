<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Process;

/**
 * Interface ErrorInterface
 */
interface ErrorInterface
{
    /**
     * Returns list of errors.
     *
     * @return array
     */
    public function getErrors();
}