<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Request\Save\Profile;

use TNW\Subscriptions\Model\ProductSubscriptionProfile\Manager as SubscriptionProductManager;

/**
 * Save modified products processor.
 */
class ModifiedProducts extends Base
{
    /**
     * Subscription profile product manager.
     *
     * @var SubscriptionProductManager
     */
    private $subscriptionProductManager;

    /**
     * @param SubscriptionProductManager $subscriptionProductManager
     */
    public function __construct(
        SubscriptionProductManager $subscriptionProductManager
    ) {
        $this->subscriptionProductManager = $subscriptionProductManager;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $this->subscriptionProductManager->processProfileProducts($data);
    }
}
