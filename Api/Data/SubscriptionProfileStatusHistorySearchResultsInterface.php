<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for subscription profile status history search results.
 */
interface SubscriptionProfileStatusHistorySearchResultsInterface extends SearchResultsInterface
{
    /**
     * Gets Subscription Profile Status History list.
     *
     * @return SubscriptionProfileStatusHistoryInterface[]
     */
    public function getItems();

    /**
     * Sets Subscription Profile Status History list.
     *
     * @param SubscriptionProfileStatusHistoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
