<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\ResourceModel\BillingFrequency as ResourceBillingFrequency;
use TNW\Subscriptions\Api\Data\BillingFrequencySearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterfaceFactory;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\BillingFrequencyRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use TNW\Subscriptions\Model\ResourceModel\BillingFrequency\CollectionFactory as BillingFrequencyCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Store\Model\StoreManagerInterface;

class BillingFrequencyRepository implements BillingFrequencyRepositoryInterface
{
    /**
     * @var BillingFrequencyCollectionFactory
     */
    private $billingFrequencyCollectionFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var BillingFrequencySearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResourceBillingFrequency
     */
    private $resource;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var BillingFrequencyFactory
     */
    private $billingFrequencyFactory;

    /**
     * @var BillingFrequencyInterfaceFactory
     */
    private $dataBillingFrequencyFactory;

    /**
     * @var BillingFrequencyUnitType
     */
    private $billingFrequencyUnitType;

    /**
     * @param ResourceBillingFrequency $resource
     * @param BillingFrequencyFactory $billingFrequencyFactory
     * @param BillingFrequencyInterfaceFactory $dataBillingFrequencyFactory
     * @param BillingFrequencyCollectionFactory $billingFrequencyCollectionFactory
     * @param BillingFrequencySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param BillingFrequencyUnitType $billingFrequencyUnitType
     */
    public function __construct(
        ResourceBillingFrequency $resource,
        BillingFrequencyFactory $billingFrequencyFactory,
        BillingFrequencyInterfaceFactory $dataBillingFrequencyFactory,
        BillingFrequencyCollectionFactory $billingFrequencyCollectionFactory,
        BillingFrequencySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        BillingFrequencyUnitType $billingFrequencyUnitType
    ) {
        $this->resource = $resource;
        $this->billingFrequencyFactory = $billingFrequencyFactory;
        $this->billingFrequencyCollectionFactory = $billingFrequencyCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataBillingFrequencyFactory = $dataBillingFrequencyFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->billingFrequencyUnitType = $billingFrequencyUnitType;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \TNW\Subscriptions\Api\Data\BillingFrequencyInterface $billingFrequency
    ) {
        /* if (empty($billingFrequency->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $billingFrequency->setStoreId($storeId);
        } */
        try {
            $this->resource->save($billingFrequency);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the billingFrequency: %1',
                $exception->getMessage()
            ));
        }
        return $billingFrequency;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($billingFrequencyId)
    {
        $billingFrequency = $this->billingFrequencyFactory->create();
        $billingFrequency->load($billingFrequencyId);
        if (!$billingFrequency->getId()) {
            throw new NoSuchEntityException(__('BillingFrequency with id "%1" does not exist.', $billingFrequencyId));
        }
        return $billingFrequency;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->billingFrequencyCollectionFactory->create();
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

        foreach ($collection as $billingFrequencyModel) {
            $billingFrequencyData = $this->dataBillingFrequencyFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $billingFrequencyData,
                $billingFrequencyModel->getData(),
                'TNW\Subscriptions\Api\Data\BillingFrequencyInterface'
            );
            $items[] = $billingFrequencyData;
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \TNW\Subscriptions\Api\Data\BillingFrequencyInterface $billingFrequency
    ) {
        try {
            $this->resource->delete($billingFrequency);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the BillingFrequency: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($billingFrequencyId)
    {
        return $this->delete($this->getById($billingFrequencyId));
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingFrequencyPeriodLabel($billingFrequency)
    {
        $billingFrequencyData = '';
        if ($billingFrequency->getFrequency()) {
            $unit = $billingFrequency->getUnit();
            if (!$unit) {
                $unit = BillingFrequencyUnitType::DAYS;
            }
            $unitLabel = $this->billingFrequencyUnitType->getLabelByValue($unit);
            $billingFrequencyData = $billingFrequency->getFrequency() . ' ' . __($unitLabel);
        }

        return $billingFrequencyData;
    }
}
