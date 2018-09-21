<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api;

/**
 * Interface for billing frequency repository.
 */
interface BillingFrequencyRepositoryInterface
{
    /**
     * Save BillingFrequency
     * @param \TNW\Subscriptions\Api\Data\BillingFrequencyInterface $billingFrequency
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \TNW\Subscriptions\Api\Data\BillingFrequencyInterface $billingFrequency
    );

    /**
     * Retrieve BillingFrequency
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencyInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Retrieve BillingFrequency matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \TNW\Subscriptions\Api\Data\BillingFrequencySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete BillingFrequency
     * @param \TNW\Subscriptions\Api\Data\BillingFrequencyInterface $billingFrequency
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\BillingFrequencyInterface $billingFrequency
    );

    /**
     * Delete BillingFrequency by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);

    /**
     * Get billing frequency unit and period label.
     *
     * @param \TNW\Subscriptions\Api\Data\BillingFrequencyInterface $billingFrequency
     * @return string
     */
    public function getBillingFrequencyPeriodLabel($billingFrequency);
}
