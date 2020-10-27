<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

interface SubscriptionProfileRepositoryInterface
{
    /**
     * Save SubscriptionProfile
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface $subscriptionProfile
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface $subscriptionProfile
    );

    /**
     * Retrieve SubscriptionProfile
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Retrieve SubscriptionProfile matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete SubscriptionProfile
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface $subscriptionProfile
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface $subscriptionProfile
    );

    /**
     * Delete SubscriptionProfile by ID
     * @param string $id
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
