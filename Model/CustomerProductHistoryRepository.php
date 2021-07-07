<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\CustomerProductHistoryRepositoryInterface;
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterface;
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterfaceFactory;
use TNW\Subscriptions\Api\Data\CustomerProductHistorySearchResultsInterface;
use TNW\Subscriptions\Api\Data\CustomerProductHistorySearchResultsInterfaceFactory;
use TNW\Subscriptions\Model\ResourceModel\CustomerProductHistory as CustomerProductHistoryResourceModel;
use TNW\Subscriptions\Model\ResourceModel\CustomerProductHistory\Collection;
use TNW\Subscriptions\Model\ResourceModel\CustomerProductHistory\CollectionFactory;

/**
 * Class CustomerProductHistoryRepository - for saving/retrieving customer product history items.
 */
class CustomerProductHistoryRepository implements CustomerProductHistoryRepositoryInterface
{
    /**
     * @var CustomerProductHistoryInterfaceFactory
     */
    private $customerProductHistoryFactory;

    /**
     * @var CustomerProductHistoryResourceModel
     */
    private $resourceModel;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    /**
     * @var CustomerProductHistorySearchResultsInterface
     */
    private $customerProductHistorySearchResultsFactory;

    /**
     * CustomerProductHistoryRepository constructor.
     * @param CustomerProductHistoryInterfaceFactory $customerProductHistoryFactory
     * @param CustomerProductHistoryResourceModel $resourceModel
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessor $collectionProcessor
     * @param CustomerProductHistorySearchResultsInterfaceFactory $customerProductHistorySearchResultsFactory
     */
    public function __construct(
        CustomerProductHistoryInterfaceFactory $customerProductHistoryFactory,
        CustomerProductHistoryResourceModel $resourceModel,
        CollectionFactory $collectionFactory,
        CollectionProcessor $collectionProcessor,
        CustomerProductHistorySearchResultsInterfaceFactory $customerProductHistorySearchResultsFactory
    ) {
        $this->customerProductHistoryFactory = $customerProductHistoryFactory;
        $this->resourceModel = $resourceModel;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->customerProductHistorySearchResultsFactory = $customerProductHistorySearchResultsFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CustomerProductHistoryInterface $model)
    {
        try {
            /** @var CustomerProductHistory $model */
            $this->resourceModel->save($model);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not save the CustomerProductHistory: %1',
                $e->getMessage()
            ));
        }
        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        /** @var CustomerProductHistory $model */
        $model = $this->customerProductHistoryFactory->create();
        $this->resourceModel->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__(
                'CustomerProductHistory with id "%1" does not exist.',
                $id
            ));
        }
        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->customerProductHistorySearchResultsFactory->create();
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setSearchCriteria($searchCriteria);

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CustomerProductHistoryInterface $model)
    {
        try {
            /** @var CustomerProductHistory $model */
            $this->resourceModel->delete($model);
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__(
                'Could not delete the CustomerProductHistory: %1',
                $e->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
