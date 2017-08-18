<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderSearchResultsInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileQueueInterface;

interface SubscriptionProfileQueueRepositoryInterface
{
    /**
     * Save Subscription Profile Queue.
     *
     * @param SubscriptionProfileQueueInterface $subscriptionProfileQueue
     * @return SubscriptionProfileQueueInterface
     * @throws LocalizedException
     */
    public function save(
        SubscriptionProfileQueueInterface $subscriptionProfileQueue
    );

    /**
     * Retrieve Subscription Profile Queue.
     *
     * @param string $id
     * @return SubscriptionProfileQueueInterface
     * @throws LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve Subscription Profile Queue matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SubscriptionProfileOrderSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Subscription Profile Queue.
     *
     * @param SubscriptionProfileQueueInterface $subscriptionProfileQueue
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        SubscriptionProfileQueueInterface $subscriptionProfileQueue
    );

    /**
     * Delete Subscription Profile Queue by Id.
     *
     * @param string $id
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);
}
