<?php

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use TNW\Subscriptions\Model\SubscriptionProfile;

interface EngineInterface
{
    /**
     * Process profile
     *
     * @param SubscriptionProfile $profile
     * @return mixed
     */
    public function processProfile(SubscriptionProfile $profile);

    /**
     * Update profile
     *
     * @param SubscriptionProfile $profile
     * @return SubscriptionProfile
     */
    public function updateProfile(SubscriptionProfile $profile);
}
