<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface ProductSubscriptionProfileSearchResultsInterface - extends default to be implemented functionality for
 * product subscription profile search results
 */
interface ProductSubscriptionProfileSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get ProductSubscriptionProfile list.
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface[]
     */
    public function getItems();

    /**
     * Set subscription_profile_id list.
     * @param \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
