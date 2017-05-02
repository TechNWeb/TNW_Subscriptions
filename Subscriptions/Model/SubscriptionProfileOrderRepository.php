<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterfaceFactory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder as ResourceSubscriptionProfileOrder;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder\CollectionFactory as SubscriptionProfileOrderCollectionFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderSearchResultsInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;

class SubscriptionProfileOrderRepository implements SubscriptionProfileOrderRepositoryInterface
{

    protected $dataObjectHelper;

    protected $searchResultsFactory;

    protected $SubscriptionProfileOrderFactory;

    protected $dataSubscriptionProfileOrderFactory;

    private $storeManager;

    protected $resource;

    protected $dataObjectProcessor;

    protected $SubscriptionProfileOrderCollectionFactory;


    /**
     * @param ResourceSubscriptionProfileOrder $resource
     * @param SubscriptionProfileOrderFactory $subscriptionProfileOrderFactory
     * @param SubscriptionProfileOrderInterfaceFactory $dataSubscriptionProfileOrderFactory
     * @param SubscriptionProfileOrderCollectionFactory $subscriptionProfileOrderCollectionFactory
     * @param SubscriptionProfileOrderSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceSubscriptionProfileOrder $resource,
        SubscriptionProfileOrderFactory $subscriptionProfileOrderFactory,
        SubscriptionProfileOrderInterfaceFactory $dataSubscriptionProfileOrderFactory,
        SubscriptionProfileOrderCollectionFactory $subscriptionProfileOrderCollectionFactory,
        SubscriptionProfileOrderSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->subscriptionProfileOrderFactory = $subscriptionProfileOrderFactory;
        $this->subscriptionProfileOrderCollectionFactory = $subscriptionProfileOrderCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSubscriptionProfileOrderFactory = $dataSubscriptionProfileOrderFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $subscriptionProfileOrder
    ) {
        /* if (empty($subscriptionProfileOrder->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $subscriptionProfileOrder->setStoreId($storeId);
        } */
        try {
            $this->resource->save($subscriptionProfileOrder);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscriptionProfileOrder: %1',
                $exception->getMessage()
            ));
        }
        return $subscriptionProfileOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($subscriptionProfileOrderId)
    {
        $subscriptionProfileOrder = $this->subscriptionProfileOrderFactory->create();
        $subscriptionProfileOrder->load($subscriptionProfileOrderId);
        if (!$subscriptionProfileOrder->getId()) {
            throw new NoSuchEntityException(__('SubscriptionProfileOrder with id "%1" does not exist.',
                $subscriptionProfileOrderId));
        }
        return $subscriptionProfileOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->subscriptionProfileOrderCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
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

        foreach ($collection as $subscriptionProfileOrderModel) {
            $subscriptionProfileOrderData = $this->dataSubscriptionProfileOrderFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $subscriptionProfileOrderData,
                $subscriptionProfileOrderModel->getData(),
                'TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface'
            );
            $items[] = $this->dataObjectProcessor->buildOutputDataArray(
                $subscriptionProfileOrderData,
                'TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface'
            );
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $subscriptionProfileOrder
    ) {
        try {
            $this->resource->delete($subscriptionProfileOrder);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SubscriptionProfileOrder: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($subscriptionProfileOrderId)
    {
        return $this->delete($this->getById($subscriptionProfileOrderId));
    }
}
