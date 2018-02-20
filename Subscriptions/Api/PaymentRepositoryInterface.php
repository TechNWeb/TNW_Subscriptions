<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api;

/**
 * Interface for payment repository.
 */
interface PaymentRepositoryInterface
{
    /**
     * Save Subscription profile payment
     *
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface $subscriptionPayment
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface $subscriptionPayment
    );

    /**
     * Retrieve Subscription profile payment
     *
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Retrieve Subscription profile payments matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Subscription profile payment
     *
     * @param \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface $subscriptionPayment
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface $subscriptionPayment
    );

    /**
     * Delete Subscription profile payment by ID
     *
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id);
}
