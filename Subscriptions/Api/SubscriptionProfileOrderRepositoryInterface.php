<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SubscriptionProfileOrderRepositoryInterface
{


    /**
     * Save SubscriptionProfileOrder
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $subscriptionProfileOrder
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $subscriptionProfileOrder
    );

    /**
     * Retrieve SubscriptionProfileOrder
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Retrieve SubscriptionProfileOrder matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete SubscriptionProfileOrder
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $subscriptionProfileOrder
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $subscriptionProfileOrder
    );

    /**
     * Delete SubscriptionProfileOrder by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
