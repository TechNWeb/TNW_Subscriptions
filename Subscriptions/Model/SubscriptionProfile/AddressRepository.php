<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressSearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileAddressRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Address as ResourceProfileAddress;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Address\CollectionFactory as AddressCollectionFactory;

/**
 * Repository for saving/retrieving subscription profile addresses.
 */
class AddressRepository implements SubscriptionProfileAddressRepositoryInterface
{
    /**
     * @var AddressCollectionFactory
     */
    private $addressCollectionFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SubscriptionProfileAddressSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var ResourceProfileAddress
     */
    private $resource;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var SubscriptionProfileAddressInterfaceFactory
     */
    private $dataAddressFactory;

    /**
     * AddressRepository constructor.
     * @param ResourceProfileAddress $resource
     * @param AddressFactory $profileAddressFactory
     * @param SubscriptionProfileAddressInterfaceFactory $dataProfileAddressFactory
     * @param AddressCollectionFactory $addressCollectionFactory
     * @param SubscriptionProfileAddressSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        ResourceProfileAddress $resource,
        AddressFactory $profileAddressFactory,
        SubscriptionProfileAddressInterfaceFactory $dataProfileAddressFactory,
        AddressCollectionFactory $addressCollectionFactory,
        SubscriptionProfileAddressSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->resource = $resource;
        $this->addressFactory = $profileAddressFactory;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataAddressFactory = $dataProfileAddressFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        SubscriptionProfileAddressInterface $profileAddress
    ) {
        try {
            $this->resource->save($profileAddress);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the subscription profile address: %1',
                $e->getMessage()
            ));
        }

        return $profileAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($profileAddressId)
    {
        $profileAddress = $this->addressFactory->create();
        $profileAddress->load($profileAddressId);

        if (!$profileAddress->getId()) {
            throw new NoSuchEntityException(
                __('Subscription profile address with id "%1" does not exist.', $profileAddressId)
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

        $collection = $this->addressCollectionFactory->create();

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

        foreach ($collection as $profileAddressModel) {
            $profileAddressData = $this->dataAddressFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $profileAddressData,
                $profileAddressModel->getData(),
                SubscriptionProfileAddressInterface::class
            );
            $items[] = $profileAddressData;
        }
        $searchResults->setItems($items);

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        SubscriptionProfileAddressInterface $profileAddress
    ) {
        try {
            $this->resource->delete($profileAddress);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the subscription profile address: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($profileAddressId)
    {
        return $this->delete($this->getById($profileAddressId));
    }
}
