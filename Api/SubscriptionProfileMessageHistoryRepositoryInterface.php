<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressSearchResultsInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterface;

/**
 * Interface SubscriptionProfileMessageHistoryRepositoryInterface - determines the functionality to implement for
 * subscription profile message history repository
 */
interface SubscriptionProfileMessageHistoryRepositoryInterface
{
    /**
     * Saves Subscription Profile Message History.
     *
     * @param SubscriptionProfileMessageHistoryInterface $messageHistory
     * @return SubscriptionProfileMessageHistoryInterface
     * @throws LocalizedException
     */
    public function save(SubscriptionProfileMessageHistoryInterface $messageHistory);

    /**
     * Retrieves Subscription Profile Message History.
     *
     * @param int $id
     * @return SubscriptionProfileMessageHistoryInterface
     * @throws LocalizedException
     */
    public function getById($id);

    /**
     * Retrieves Subscription Profile Message History matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SubscriptionProfileAddressSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Deletes Subscription Profile Message History.
     *
     * @param SubscriptionProfileMessageHistoryInterface $messageHistory
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(SubscriptionProfileMessageHistoryInterface $messageHistory);

    /**
     * Deletes Subscription Profile Message History by ID.
     *
     * @param string $id
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);
}
