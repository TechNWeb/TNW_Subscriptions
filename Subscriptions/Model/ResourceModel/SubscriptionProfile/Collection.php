<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;

/**
 * Collection class for Subscription Profile model.
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resources
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'TNW\Subscriptions\Model\SubscriptionProfile',
            'TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile'
        );
    }
}
