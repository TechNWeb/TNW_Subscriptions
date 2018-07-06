<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface CustomerQuoteSearchResultsInterface
 */
interface CustomerQuoteSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Gets Customer Quote list.
     *
     * @return CustomerQuoteInterface[]
     */
    public function getItems();

    /**
     * Sets Customer Quote list.
     *
     * @param CustomerQuoteInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
