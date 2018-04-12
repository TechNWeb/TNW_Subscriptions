<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

/**
 * Interface for subscription profile payment search result
 */
interface SubscriptionProfilePaymentSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get Subscription profile payments list.
     *
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface[]
     */
    public function getItems();

    /**
     * Set Subscription profile payments list.
     *
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
