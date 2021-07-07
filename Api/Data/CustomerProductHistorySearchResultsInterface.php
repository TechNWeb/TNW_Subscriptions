<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for customer products history search results.
 */
interface CustomerProductHistorySearchResultsInterface extends SearchResultsInterface
{
    /**
     * {@inheritdoc}
     */
    public function getItems();

    /**
     * {@inheritdoc}
     */
    public function setItems(array $items);
}
