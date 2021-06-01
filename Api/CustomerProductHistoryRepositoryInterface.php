<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterface;
use TNW\Subscriptions\Api\Data\CustomerProductHistorySearchResultsInterface;

/**
 * Interface for customer products history repository.
 */
interface CustomerProductHistoryRepositoryInterface
{
    /**
     * Save CustomerProductHistory
     *
     * @param CustomerProductHistoryInterface $model
     * @return CustomerProductHistoryInterface
     * @throws CouldNotSaveException
     */
    public function save(CustomerProductHistoryInterface $model);

    /**
     * Retrieve CustomerProductHistory
     *
     * @param string $id
     * @return CustomerProductHistoryInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * Retrieve CustomerProductHistory matching the specified criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CustomerProductHistorySearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete CustomerProductHistory
     *
     * @param CustomerProductHistoryInterface $model
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(CustomerProductHistoryInterface $model);

    /**
     * Delete CustomerProductHistory by ID
     *
     * @param string $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($id);
}
