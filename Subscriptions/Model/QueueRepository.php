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
use TNW\Subscriptions\Api\Data\SubscriptionProfileQueueInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileQueueInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileQueueSearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileQueueRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\Queue as ResourceQueue;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory as QueueCollectionFactory;

/**
 * Repository for saving/retrieving profile queue items.
 */
class QueueRepository implements SubscriptionProfileQueueRepositoryInterface
{
    /**
     * @var QueueCollectionFactory
     */
    private $queueCollectionFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SubscriptionProfileQueueSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var ResourceQueue
     */
    private $resource;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var QueueFactory
     */
    private $queueFactory;

    /**
     * @var SubscriptionProfileQueueInterfaceFactory
     */
    private $dataQueueFactory;

    /**
     * QueueRepository constructor.
     * @param ResourceQueue $resource
     * @param QueueFactory $profileAddressFactory
     * @param SubscriptionProfileQueueInterfaceFactory $dataProfileAddressFactory
     * @param QueueCollectionFactory $addressCollectionFactory
     * @param SubscriptionProfileQueueSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        ResourceQueue $resource,
        QueueFactory $profileAddressFactory,
        SubscriptionProfileQueueInterfaceFactory $dataProfileAddressFactory,
        QueueCollectionFactory $addressCollectionFactory,
        SubscriptionProfileQueueSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->resource = $resource;
        $this->queueFactory = $profileAddressFactory;
        $this->queueCollectionFactory = $addressCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataQueueFactory = $dataProfileAddressFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        SubscriptionProfileQueueInterface $profileQueue
    ) {
        try {
            $this->resource->save($profileQueue);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription profile queue item: %1',
                $e->getMessage()
            ));
        }

        return $profileQueue;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($queueId)
    {
        $profileAddress = $this->queueFactory->create();
        $profileAddress->load($queueId);

        if (!$profileAddress->getId()) {
            throw new NoSuchEntityException(
                __('Subscription profile queue item with id "%1" does not exist.', $queueId)
            );
        }

        return $profileAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->queueCollectionFactory->create();

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

        foreach ($collection as $profileQueueModel) {
            $profileQueueData = $this->dataQueueFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $profileQueueData,
                $profileQueueModel->getData(),
                SubscriptionProfileQueueInterface::class
            );
            $items[] = $profileQueueData;
        }
        $searchResults->setItems($items);

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        SubscriptionProfileQueueInterface $profileQueue
    ) {
        try {
            $this->resource->delete($profileQueue);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the subscription profile queue item: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($queueId)
    {
        return $this->delete($this->getById($queueId));
    }
}
