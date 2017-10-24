<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Request\Save\Profile;

use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Save payment method processor.
 */
class Payment extends Base
{
    /**
     * Subscription profile manager
     *
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
     * @inheritdoc
     */
    public function process(array $data)
    {
        $this->profileManager->processPaymentMethod($data);
    }
}
