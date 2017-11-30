<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistorySearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileStatusHistoryRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileStatusHistory as ResourceSubscriptionProfileStatusHistory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileStatusHistory\CollectionFactory
    as SubscriptionProfileStatusHistoryCollectionFactory;

/**
 * Repository for subscription profile status history.
 */
class SubscriptionProfileStatusHistoryRepository implements SubscriptionProfileStatusHistoryRepositoryInterface
{
    /**
     * @var ResourceSubscriptionProfileStatusHistory
     */
    private $resource;

    /**
     * @var SubscriptionProfileStatusHistoryFactory
     */
    private $modelFactory;

    /**
     * @var SubscriptionProfileStatusHistoryInterfaceFactory
     */
    private $modelInterfaceFactory;

    /**
     * @var SubscriptionProfileStatusHistoryCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SubscriptionProfileStatusHistorySearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * SubscriptionProfileStatusHistoryRepository constructor.
     * @param ResourceSubscriptionProfileStatusHistory $resource
     * @param SubscriptionProfileStatusHistoryFactory $modelFactory
     * @param SubscriptionProfileStatusHistoryInterfaceFactory $modelInterfaceFactory
     * @param SubscriptionProfileStatusHistoryCollectionFactory $collectionFactory
     * @param SubscriptionProfileStatusHistorySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        ResourceSubscriptionProfileStatusHistory $resource,
        SubscriptionProfileStatusHistoryFactory $modelFactory,
        SubscriptionProfileStatusHistoryInterfaceFactory $modelInterfaceFactory,
        SubscriptionProfileStatusHistoryCollectionFactory $collectionFactory,
        SubscriptionProfileStatusHistorySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->resource = $resource;
        $this->modelFactory = $modelFactory;
        $this->modelInterfaceFactory = $modelInterfaceFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        SubscriptionProfileStatusHistoryInterface $model
    ) {
        try {
            $this->resource->save($model);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription profile status history item: %1',
                $e->getMessage()
            ));
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        $result = $this->modelFactory->create();
        $result->load($id);

        if (!$result->getId()) {
            throw new NoSuchEntityException(
                __('Subscription profile status history item with id "%1" does not exist.', $id)
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->collectionFactory->create();

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();

        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }

        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $items = [];

        foreach ($collection as $model) {
            $modelData = $this->modelInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $modelData,
                $model->getData(),
                SubscriptionProfileStatusHistoryInterface::class
            );
            $items[] = $modelData;
        }
        $searchResults->setItems($items);

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        SubscriptionProfileStatusHistoryInterface $model
    ) {
        try {
            $this->resource->delete($model);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the subscription profile status history item: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
