<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

/**
 * Interface ProductBillingFrequencySearchResultsInterface - used to define additional functionality to be implemented
 * for product BF
 */
interface ProductBillingFrequencySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get ProductBillingFrequency list.
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface[]
     */
    public function getItems();

    /**
     * Set billing_frequency_id list.
     * @param \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
