<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\CustomerQuoteSearchResultsInterface;
use TNW\Subscriptions\Api\Data\CustomerQuoteInterface;

/**
 * Interface CustomerQuoteRepositoryInterface
 */
interface CustomerQuoteRepositoryInterface
{
    /**
     * Save Customer quote.
     *
     * @param CustomerQuoteInterface $customerQuote
     * @return CustomerQuoteInterface
     * @throws LocalizedException
     */
    public function save(
        CustomerQuoteInterface $customerQuote
    );

    /**
     * Retrieve Customer quote.
     *
     * @param string $id
     * @return CustomerQuoteInterface
     * @throws LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve Customer quote matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CustomerQuoteSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Customer quote.
     *
     * @param CustomerQuoteInterface $customerQuote
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        CustomerQuoteInterface $customerQuote
    );

    /**
     * Delete Customer quote by Id.
     *
     * @param string $id
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);

    /**
     * Save customer quote by customer id and quote id
     *
     * @param string $customerId
     * @param string $quoteId
     */
    public function saveCustomerQuote($customerId, $quoteId);

    /**
     * Returns customer quotes by customer id
     * @param string $customerId
     * @return CustomerQuoteInterface[]
     */
    public function getCustomerQuotes($customerId);
}
