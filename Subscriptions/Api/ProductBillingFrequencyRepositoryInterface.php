<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface ProductBillingFrequencyRepositoryInterface
{


    /**
     * Save ProductBillingFrequency
     * @param \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface $productBillingFrequency
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface $productBillingFrequency
    );

    /**
     * Retrieve ProductBillingFrequency
     * @param string $id
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve ProductBillingFrequency matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Retrieve ProductBillingFrequency by product id
     * @param string
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListByProductId(
        $productId
    );

    /**
     * Retrieve ProductBillingFrequency by frequency id
     * @param string $frequncyId
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListByFrequencyId(
        $frequncyId
    );

    /**
     * Delete ProductBillingFrequency
     * @param \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface $productBillingFrequency
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface $productBillingFrequency
    );

    /**
     * Delete ProductBillingFrequency by ID
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
