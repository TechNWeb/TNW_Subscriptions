<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

/**
 * Interface ModifierInterface
 */
interface ModifierInterface
{
    /**
     * Modifies subscription profile statuses.
     *
     * @param array $ids
     * @return void
     */
    public function modify(array $ids);
}