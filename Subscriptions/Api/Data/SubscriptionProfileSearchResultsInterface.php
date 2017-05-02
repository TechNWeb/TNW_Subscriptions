<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

interface SubscriptionProfileSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{


    /**
     * Get SubscriptionProfile list.
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface[]
     */
    public function getItems();

    /**
     * Set customer_id list.
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
