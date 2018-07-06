<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SubscriptionProfileQueueSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Gets Subscription Profile Queue list.
     *
     * @return SubscriptionProfileInterface[]
     */
    public function getItems();

    /**
     * Sets Subscription Profile Queue list.
     *
     * @param SubscriptionProfileInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
