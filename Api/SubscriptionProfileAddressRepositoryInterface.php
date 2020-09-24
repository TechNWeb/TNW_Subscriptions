<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressSearchResultsInterface;

interface SubscriptionProfileAddressRepositoryInterface
{
    /**
     * Saves Subscription Profile Address.
     *
     * @param SubscriptionProfileAddressInterface $subscriptionProfileOrder
     * @return SubscriptionProfileAddressInterface
     * @throws LocalizedException
     */
    public function save(
        SubscriptionProfileAddressInterface $subscriptionProfileOrder
    );

    /**
     * Retrieves Subscription Profile Address.
     *
     * @param string $id
     * @return SubscriptionProfileAddressInterface
     * @throws LocalizedException
     */
    public function getById($id);

    /**
     * Retrieves Subscription Profile Address matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SubscriptionProfileAddressSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Deletes Subscription Profile Address.
     *
     * @param SubscriptionProfileAddressInterface $subscriptionProfileAddress
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        SubscriptionProfileAddressInterface $subscriptionProfileAddress
    );

    /**
     * Deletes Subscription Profile Address by ID.
     *
     * @param string $id
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($id);
}
