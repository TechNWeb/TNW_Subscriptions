<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ProductSubscriptionProfileRepositoryInterface
{


    /**
     * Save ProductSubscriptionProfile
     * @param \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface $productSubscriptionProfile
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface $productSubscriptionProfile
    );

    /**
     * Retrieve ProductSubscriptionProfile
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve ProductSubscriptionProfile matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ProductSubscriptionProfile
     * @param \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface $productSubscriptionProfile
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface $productSubscriptionProfile
    );

    /**
     * Delete ProductSubscriptionProfile by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
