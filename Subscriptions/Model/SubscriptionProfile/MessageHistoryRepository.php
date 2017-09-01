<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileMessageHistoryRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory as ResourceProfileMessageHistory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\CollectionFactory;

/**
 * Repository for Subscription profile message history.
 */
class MessageHistoryRepository implements SubscriptionProfileMessageHistoryRepositoryInterface
{
    /**
     * Resource profile message history.
     *
     * @var ResourceProfileMessageHistory
     */
    private $resource;

    /**
     * Message history factory.
     *
     * @var SubscriptionProfileMessageHistoryInterfaceFactory
     */
    private $messageHistoryFactory;

    /**
     * Search result factory.
     *
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * Collection factory.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Data object helper.
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @param ResourceProfileMessageHistory $resource
     * @param SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionFactory $collectionFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        ResourceProfileMessageHistory $resource,
        SubscriptionProfileMessageHistoryInterfaceFactory $messageHistoryFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionFactory $collectionFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->resource = $resource;
        $this->messageHistoryFactory = $messageHistoryFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionFactory = $collectionFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        SubscriptionProfileMessageHistoryInterface $messageHistory
    ) {
        try {
            $this->resource->save($messageHistory);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription profile history message: %1',
                $e->getMessage()
            ));
        }

        return $messageHistory;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        /** @var MessageHistory $messageHistory */
        $messageHistory = $this->messageHistoryFactory->create();
        $messageHistory->load($id);

        if (!$messageHistory->getId()) {
            throw new NoSuchEntityException(
                __('Subscription profile history message with id "%1" does not exist.', $messageHistory)
            );
        }

        return $messageHistory;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ) {
        /** @var \Magento\Framework\Api\SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        /** @var \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory\Collection $collection */
        $collection = $this->collectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $searchCriteria->getSortOrders();

        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $orderHistories = [];

        /** @var MessageHistory $customerModel */
        foreach ($collection as $orderHistoryModel) {
            $profileAddressData = $this->messageHistoryFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $profileAddressData,
                $orderHistoryModel->getData(),
                SubscriptionProfileMessageHistoryInterface::class
            );
            $orderHistories[] = $profileAddressData;
        }
        $searchResults->setItems($orderHistories);

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(SubscriptionProfileMessageHistoryInterface $messageHistory)
    {
        //todo
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        //todo
    }
}
