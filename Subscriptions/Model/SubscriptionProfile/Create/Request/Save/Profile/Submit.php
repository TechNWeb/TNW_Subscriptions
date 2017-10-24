<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Create\Request\Save\Profile;

/**
 * Create subscription profile processor.
 */
class Submit extends Base
{
    /**
     * @inheritdoc
     */
    public function process(array $data)
    {
        $this->getSubCreateModel()->createSubscriptions();
    }
}
