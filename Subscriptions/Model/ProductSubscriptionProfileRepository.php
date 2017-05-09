<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\ProductSubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileSearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile as ResourceProductSubscriptionProfile;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\CollectionFactory as ProductSubscriptionProfileCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class ProductSubscriptionProfileRepository implements ProductSubscriptionProfileRepositoryInterface
{

    private $resource;

    private $productSubscriptionProfileFactory;

    private $productSubscriptionProfileCollectionFactory;

    private $searchResultsFactory;

    private $dataObjectHelper;

    private $dataObjectProcessor;

    private $dataProductSubscriptionProfileFactory;

    private $storeManager;


    /**
     * @param ResourceProductSubscriptionProfile $resource
     * @param ProductSubscriptionProfileFactory $productSubscriptionProfileFactory
     * @param ProductSubscriptionProfileInterfaceFactory $dataProductSubscriptionProfileFactory
     * @param ProductSubscriptionProfileCollectionFactory $productSubscriptionProfileCollectionFactory
     * @param ProductSubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceProductSubscriptionProfile $resource,
        ProductSubscriptionProfileFactory $productSubscriptionProfileFactory,
        ProductSubscriptionProfileInterfaceFactory $dataProductSubscriptionProfileFactory,
        ProductSubscriptionProfileCollectionFactory $productSubscriptionProfileCollectionFactory,
        ProductSubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->productSubscriptionProfileFactory = $productSubscriptionProfileFactory;
        $this->productSubscriptionProfileCollectionFactory = $productSubscriptionProfileCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProductSubscriptionProfileFactory = $dataProductSubscriptionProfileFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface $productSubscriptionProfile
    ) {
        /* if (empty($productSubscriptionProfile->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $productSubscriptionProfile->setStoreId($storeId);
        } */
        try {
            $this->resource->save($productSubscriptionProfile);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the productSubscriptionProfile: %1',
                $exception->getMessage()
            ));
        }
        return $productSubscriptionProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($productSubscriptionProfileId)
    {
        $productSubscriptionProfile = $this->productSubscriptionProfileFactory->create();
        $productSubscriptionProfile->load($productSubscriptionProfileId);
        if (!$productSubscriptionProfile->getId()) {
            throw new NoSuchEntityException(__('ProductSubscriptionProfile with id "%1" does not exist.',
                $productSubscriptionProfileId));
        }
        return $productSubscriptionProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->productSubscriptionProfileCollectionFactory->create();
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

        foreach ($collection as $productSubscriptionProfileModel) {
            $productSubscriptionProfileData = $this->dataProductSubscriptionProfileFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $productSubscriptionProfileData,
                $productSubscriptionProfileModel->getData(),
                'TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface'
            );
            $items[] = $this->dataObjectProcessor->buildOutputDataArray(
                $productSubscriptionProfileData,
                'TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface'
            );
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface $productSubscriptionProfile
    ) {
        try {
            $this->resource->delete($productSubscriptionProfile);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ProductSubscriptionProfile: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($productSubscriptionProfileId)
    {
        return $this->delete($this->getById($productSubscriptionProfileId));
    }
}
