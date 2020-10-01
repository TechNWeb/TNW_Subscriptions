<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Api\ProductSubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileSearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile as ResourceProductSubscriptionProfile;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\CollectionFactory
    as ProductSubscriptionProfileCollectionFactory;

/**
 * Repository for subscription profile products
 */
class ProductSubscriptionProfileRepository implements ProductSubscriptionProfileRepositoryInterface
{
    /**
     * @var ResourceProductSubscriptionProfile
     */
    private $resource;

    /**
     * @var ProductSubscriptionProfileFactory
     */
    private $productSubscriptionProfileFactory;

    /**
     * @var ProductSubscriptionProfileCollectionFactory
     */
    private $productSubscriptionProfileCollectionFactory;

    /**
     * @var ProductSubscriptionProfileSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ProductSubscriptionProfileInterfaceFactory
     */
    private $dataProductSubscriptionProfileFactory;

    /**
     * @param ResourceProductSubscriptionProfile $resource
     * @param ProductSubscriptionProfileFactory $productSubscriptionProfileFactory
     * @param ProductSubscriptionProfileInterfaceFactory $dataProductSubscriptionProfileFactory
     * @param ProductSubscriptionProfileCollectionFactory $productSubscriptionProfileCollectionFactory
     * @param ProductSubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        ResourceProductSubscriptionProfile $resource,
        ProductSubscriptionProfileFactory $productSubscriptionProfileFactory,
        ProductSubscriptionProfileInterfaceFactory $dataProductSubscriptionProfileFactory,
        ProductSubscriptionProfileCollectionFactory $productSubscriptionProfileCollectionFactory,
        ProductSubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->resource = $resource;
        $this->productSubscriptionProfileFactory = $productSubscriptionProfileFactory;
        $this->productSubscriptionProfileCollectionFactory = $productSubscriptionProfileCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProductSubscriptionProfileFactory = $dataProductSubscriptionProfileFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(
        \TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface $productSubscriptionProfile
    ) {
        try {
            $this->resource->save($productSubscriptionProfile);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the productSubscriptionProfile: %1',
                $exception->getMessage()
            ), $exception);
        }
        return $productSubscriptionProfile;
    }

    /**
     * @param string $productSubscriptionProfileId
     * @return ProductSubscriptionProfileInterface|ProductSubscriptionProfile
     * @throws NoSuchEntityException
     */
    public function getById($productSubscriptionProfileId)
    {
        $productSubscriptionProfile = $this->productSubscriptionProfileFactory->create();
        $productSubscriptionProfile->load($productSubscriptionProfileId);
        if (!$productSubscriptionProfile->getId()) {
            throw new NoSuchEntityException(__(
                'ProductSubscriptionProfile with id "%1" does not exist.',
                $productSubscriptionProfileId
            ));
        }
        $this->addChildren($productSubscriptionProfile);
        return $productSubscriptionProfile;
    }

    /**
     * @inheritdoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->productSubscriptionProfileCollectionFactory->create();
        $collection->addAttributeToSelect('*');

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

        $collection->load();

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        foreach ($collection as $productSubscriptionProfileModel) {
            $this->addChildren($productSubscriptionProfileModel);
        }

        return $searchResults;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function deleteById($productSubscriptionProfileId)
    {
        return $this->delete($this->getById($productSubscriptionProfileId));
    }

    /**
     * Returns child products for profile product.
     *
     * @param ProductSubscriptionProfileInterface $productSubscriptionProfileData
     * @return void
     */
    private function addChildren(
        ProductSubscriptionProfileInterface $productSubscriptionProfileData
    ) {
        $collection = $this->productSubscriptionProfileCollectionFactory->create();
        /** @var ProductSubscriptionProfileInterface[] $items */
        $items = $collection->addFieldToFilter(
            ProductSubscriptionProfileInterface::PARENT_ID,
            $productSubscriptionProfileData->getId()
        )->getItems();
        $productSubscriptionProfileData->setChildren($items);
    }
}
