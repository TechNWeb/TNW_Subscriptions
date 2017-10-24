<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Response\Save\Profile;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ResponseInterface;

/**
 * Base responce processor.
 */
abstract class Base implements ResponseInterface
{
    /**
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @param ProfileManager $profileManager
     */
    public function __construct(
        ProfileManager $profileManager
    ) {
        $this->profileManager = $profileManager;
    }

    /**
     * Return current subscription profile.
     *
     * @return SubscriptionProfileInterface
     */
    protected function getSubscriptionProfile()
    {
        return $this->profileManager->getProfile();
    }
}
