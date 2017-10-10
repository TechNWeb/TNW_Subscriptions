<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use TNW\Subscriptions\Api\CustomerQuoteRepositoryInterface;
use TNW\Subscriptions\Api\Data\CustomerQuoteInterface;
use TNW\Subscriptions\Api\Data\CustomerQuoteInterfaceFactory;
use TNW\Subscriptions\Api\Data\CustomerQuoteSearchResultsInterfaceFactory;
use TNW\Subscriptions\Model\ResourceModel\CustomerQuote as ResourceCustomerQuote;
use TNW\Subscriptions\Model\ResourceModel\CustomerQuote\CollectionFactory as CustomerQuoteCollectionFactory;

/**
 * Repository for saving/retrieving customer quote items.
 */
class CustomerQuoteRepository implements CustomerQuoteRepositoryInterface
{
    /**
     * @var CustomerQuoteCollectionFactory
     */
    private $customerQuoteCollectionFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CustomerQuoteSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var ResourceCustomerQuote
     */
    private $resource;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var CustomerQuoteFactory
     */
    private $customerQuoteFactory;

    /**
     * @var CustomerQuoteInterfaceFactory
     */
    private $dataCustomerQuoteFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * CustomerQuoteRepository constructor.
     * @param ResourceCustomerQuote $resource
     * @param CustomerQuoteFactory $profileAddressFactory
     * @param CustomerQuoteInterfaceFactory $dataProfileAddressFactory
     * @param CustomerQuoteCollectionFactory $customerQuoteCollectionFactory
     * @param CustomerQuoteSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ResourceCustomerQuote $resource,
        CustomerQuoteFactory $profileAddressFactory,
        CustomerQuoteInterfaceFactory $dataProfileAddressFactory,
        CustomerQuoteCollectionFactory $customerQuoteCollectionFactory,
        CustomerQuoteSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource = $resource;
        $this->customerQuoteFactory = $profileAddressFactory;
        $this->customerQuoteCollectionFactory = $customerQuoteCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCustomerQuoteFactory = $dataProfileAddressFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        CustomerQuoteInterface $customerQuote
    ) {
        try {
            $this->resource->save($customerQuote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the customer quote item: %1',
                $e->getMessage()
            ));
        }

        return $customerQuote;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($customerQuoteId)
    {
        $profileAddress = $this->customerQuoteFactory->create();
        $profileAddress->load($customerQuoteId);

        if (!$profileAddress->getId()) {
            throw new NoSuchEntityException(
                __('Customer quote item with id "%1" does not exist.', $customerQuoteId)
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

        $collection = $this->customerQuoteCollectionFactory->create();

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

        foreach ($collection as $customerQuoteModel) {
            $customerQuoteData = $this->dataCustomerQuoteFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $customerQuoteData,
                $customerQuoteModel->getData(),
                CustomerQuoteInterface::class
            );
            $items[] = $customerQuoteData;
        }
        $searchResults->setItems($items);

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        CustomerQuoteInterface $customerQuote
    ) {
        try {
            $this->resource->delete($customerQuote);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the customer quote item: %1',
                $exception->getMessage()
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($customerQuoteId)
    {
        return $this->delete($this->getById($customerQuoteId));
    }

    /**
     * {@inheritdoc}
     */
    public function saveCustomerQuote($customerId, $quoteId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                CustomerQuote::CUSTOMER_ID,
                $customerId
            )
            ->addFilter(
                CustomerQuote::QUOTE_ID,
                $quoteId
            )
            ->create();
        /** @var CustomerQuoteInterface[] $customerQuotes */
        $customerQuotes = $this->getList($searchCriteria)->getItems();
        if (!count($customerQuotes)) {
            /** @var CustomerQuoteInterface $newCustomerQuote */
            $newCustomerQuote = $this->customerQuoteFactory->create();
            $newCustomerQuote->setCustomerId($customerId);
            $newCustomerQuote->setQuoteId($quoteId);
            $this->save($newCustomerQuote);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerQuotes($customerId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                CustomerQuote::CUSTOMER_ID,
                $customerId
            )
            ->create();
        return $this->getList($searchCriteria)->getItems();
    }
}
