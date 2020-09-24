<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SubscriptionProfileOrderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Gets Subscription Profile Order list.
     *
     * @return SubscriptionProfileOrderInterface[]
     */
    public function getItems();

    /**
     * Sets sSubscription Profile Order list.
     *
     * @param SubscriptionProfileOrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
