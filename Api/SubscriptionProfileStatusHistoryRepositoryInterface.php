<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

/**
 * Interface for subscription profile status history repository.
 */
interface SubscriptionProfileStatusHistoryRepositoryInterface
{
    /**
     * Save SubscriptionProfileStatusHistory
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface $model
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface $model
    );

    /**
     * Retrieve SubscriptionProfileStatusHistory
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Retrieve SubscriptionProfileStatusHistory matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete SubscriptionProfileStatusHistory
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface $model
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface $model
    );

    /**
     * Delete SubscriptionProfileStatusHistory by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($id);
}
