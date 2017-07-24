<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;

interface EngineInterface
{
    /**
     * Process profile
     *
     * @param SubscriptionProfileInterface $profile
     * @return mixed
     */
    public function processProfile(SubscriptionProfileInterface $profile);

    /**
     * Update profile
     *
     * @param SubscriptionProfileInterface $profile
     * @return SubscriptionProfileInterface
     */
    public function updateProfile(SubscriptionProfileInterface $profile);
}
