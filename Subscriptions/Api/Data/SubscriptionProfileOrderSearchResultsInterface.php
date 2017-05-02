<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileOrderSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get SubscriptionProfileOrder list.
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface[]
     */
    public function getItems();

    /**
     * Set subscription_profile_id list.
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
