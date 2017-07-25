<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SubscriptionProfileSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Gets Subscription Profile list.
     *
     * @return SubscriptionProfileInterface[]
     */
    public function getItems();

    /**
     * Sets Subscription Profile list.
     *
     * @param SubscriptionProfileInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
