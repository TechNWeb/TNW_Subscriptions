<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencySearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterfaceFactory;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SortOrder;
use TNW\Subscriptions\Api\ProductBillingFrequencyRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\CollectionFactory as ProductBillingFrequencyCollectionFactory;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency as ResourceProductBillingFrequency;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Store\Model\StoreManagerInterface;

class ProductBillingFrequencyRepository implements ProductBillingFrequencyRepositoryInterface
{

    private $dataObjectHelper;

    private $searchResultsFactory;

    private $dataProductBillingFrequencyFactory;

    private $productBillingFrequencyFactory;

    private $productBillingFrequencyCollectionFactory;

    private $storeManager;

    private $resource;

    private $dataObjectProcessor;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;


    /**
     * @param ResourceProductBillingFrequency $resource
     * @param ProductBillingFrequencyFactory $productBillingFrequencyFactory
     * @param ProductBillingFrequencyInterfaceFactory $dataProductBillingFrequencyFactory
     * @param ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory
     * @param ProductBillingFrequencySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ResourceProductBillingFrequency $resource,
        ProductBillingFrequencyFactory $productBillingFrequencyFactory,
        ProductBillingFrequencyInterfaceFactory $dataProductBillingFrequencyFactory,
        ProductBillingFrequencyCollectionFactory $productBillingFrequencyCollectionFactory,
        ProductBillingFrequencySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource = $resource;
        $this->productBillingFrequencyFactory = $productBillingFrequencyFactory;
        $this->productBillingFrequencyCollectionFactory = $productBillingFrequencyCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataProductBillingFrequencyFactory = $dataProductBillingFrequencyFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        ProductBillingFrequencyInterface $productBillingFrequency
    ) {
        /* if (empty($productBillingFrequency->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $productBillingFrequency->setStoreId($storeId);
        } */
        try {
            $this->resource->save($productBillingFrequency);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the productBillingFrequency: %1',
                $exception->getMessage()
            ));
        }
        return $productBillingFrequency;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($productBillingFrequencyId)
    {
        $productBillingFrequency = $this->productBillingFrequencyFactory->create();
        $productBillingFrequency->load($productBillingFrequencyId);
        if (!$productBillingFrequency->getId()) {
            throw new NoSuchEntityException(__('ProductBillingFrequency with id "%1" does not exist.',
                $productBillingFrequencyId));
        }
        return $productBillingFrequency;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->productBillingFrequencyCollectionFactory->create();
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

        foreach ($collection as $productBillingFrequencyModel) {
            $productBillingFrequencyData = $this->dataProductBillingFrequencyFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $productBillingFrequencyData,
                $productBillingFrequencyModel->getData(),
                'TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface'
            );
            $items[] = $productBillingFrequencyData;
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        ProductBillingFrequencyInterface $productBillingFrequency
    ) {
        try {
            $this->resource->delete($productBillingFrequency);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ProductBillingFrequency: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($productBillingFrequencyId)
    {
        return $this->delete($this->getById($productBillingFrequencyId));
    }

    /**
     * Retrieve ProductBillingFrequency by product id
     * @param string
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListByProductId(
        $productId
    ) {
        $this->searchCriteriaBuilder->addFilter(
            ProductBillingFrequencyInterface::MAGENTO_PRODUCT_ID,
            $productId,
            'eq'
        );

        /** @var \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->getList($searchCriteria);
    }

    /**
     * Retrieve ProductBillingFrequency by frequency id
     * @param string $frequencyId
     * @return \TNW\Subscriptions\Api\Data\ProductBillingFrequencySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListByFrequencyId(
        $frequencyId
    ) {
        $this->searchCriteriaBuilder->addFilter(
            ProductBillingFrequencyInterface::BILLING_FREQUENCY_ID,
            $frequencyId,
            'eq'
        );

        /** @var \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder->create();

        return $this->getList($searchCriteria);
    }
}
