<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface BillingFrequencySearchResultsInterface - determines additional functionality to be implemented
 * in BF search result
 */
interface BillingFrequencySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get BillingFrequency list.
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface[]
     */
    public function getItems();

    /**
     * Set unit list.
     * @param \TNW\Subscriptions\Api\Data\BillingFrequencyInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
