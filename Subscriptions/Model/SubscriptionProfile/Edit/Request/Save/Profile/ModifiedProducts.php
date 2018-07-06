<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Edit\Request\Save\Profile;

use TNW\Subscriptions\Model\ProductSubscriptionProfile\Manager as SubscriptionProductManager;
use TNW\Subscriptions\Model\ProductSubscriptionProfile\ManagerConfigurable;

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
     * Subscription profile configurable product manager.
     *
     * @var ManagerConfigurable
     */
    private $configurableProductManager;

    /**
     * @param SubscriptionProductManager $subscriptionProductManager
     * @param ManagerConfigurable $configurableProductManager
     */
    public function __construct(
        SubscriptionProductManager $subscriptionProductManager,
        ManagerConfigurable $configurableProductManager
    ) {
        $this->subscriptionProductManager = $subscriptionProductManager;
        $this->configurableProductManager = $configurableProductManager;
    }

    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $this->subscriptionProductManager->processProfileProducts($data);
        $this->configurableProductManager->processProfileUpdate($data);
    }
}
