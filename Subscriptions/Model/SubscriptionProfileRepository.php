<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\Data\SubscriptionProfileSearchResultsInterfaceFactory;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SortOrder;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory as SubscriptionProfileCollectionFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as ResourceSubscriptionProfile;
use Magento\Framework\Api\DataObjectHelper;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Store\Model\StoreManagerInterface;

class SubscriptionProfileRepository implements SubscriptionProfileRepositoryInterface
{

    private $dataObjectHelper;

    private $searchResultsFactory;

    private $subscriptionProfileCollectionFactory;

    private $dataSubscriptionProfileFactory;

    private $storeManager;

    private $subscriptionProfileFactory;

    private $resource;

    private $dataObjectProcessor;


    /**
     * @param ResourceSubscriptionProfile $resource
     * @param SubscriptionProfileFactory $subscriptionProfileFactory
     * @param SubscriptionProfileInterfaceFactory $dataSubscriptionProfileFactory
     * @param SubscriptionProfileCollectionFactory $subscriptionProfileCollectionFactory
     * @param SubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceSubscriptionProfile $resource,
        SubscriptionProfileFactory $subscriptionProfileFactory,
        SubscriptionProfileInterfaceFactory $dataSubscriptionProfileFactory,
        SubscriptionProfileCollectionFactory $subscriptionProfileCollectionFactory,
        SubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->subscriptionProfileCollectionFactory = $subscriptionProfileCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSubscriptionProfileFactory = $dataSubscriptionProfileFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface $subscriptionProfile
    ) {
        /* if (empty($subscriptionProfile->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $subscriptionProfile->setStoreId($storeId);
        } */
        try {
            $this->resource->save($subscriptionProfile);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscriptionProfile: %1',
                $exception->getMessage()
            ));
        }
        return $subscriptionProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($subscriptionProfileId)
    {
        $subscriptionProfile = $this->subscriptionProfileFactory->create();
        $subscriptionProfile->load($subscriptionProfileId);
        if (!$subscriptionProfile->getId()) {
            throw new NoSuchEntityException(__('SubscriptionProfile with id "%1" does not exist.',
                $subscriptionProfileId));
        }
        return $subscriptionProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->subscriptionProfileCollectionFactory->create();
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

        foreach ($collection as $subscriptionProfileModel) {
            $subscriptionProfileData = $this->dataSubscriptionProfileFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $subscriptionProfileData,
                $subscriptionProfileModel->getData(),
                'TNW\Subscriptions\Api\Data\SubscriptionProfileInterface'
            );
            $items[] = $this->dataObjectProcessor->buildOutputDataArray(
                $subscriptionProfileData,
                'TNW\Subscriptions\Api\Data\SubscriptionProfileInterface'
            );
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileInterface $subscriptionProfile
    ) {
        try {
            $this->resource->delete($subscriptionProfile);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SubscriptionProfile: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($subscriptionProfileId)
    {
        return $this->delete($this->getById($subscriptionProfileId));
    }
}
