<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SubscriptionProfileAddressSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get SubscriptionProfileOrder list.
     *
     * @return SubscriptionProfileAddressInterface[]
     */
    public function getItems();

    /**
     * Set subscription_profile_id list.
     *
     * @param SubscriptionProfileAddressInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
