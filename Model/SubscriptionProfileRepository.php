<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileAddressInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileSearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile as ResourceSubscriptionProfile;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory as SubscriptionProfileCollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\AddressRepository;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\HistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\HistoryManager;

/**
 * Repository for subscription profiles.
 */
class SubscriptionProfileRepository implements SubscriptionProfileRepositoryInterface
{
    /**
     * @var SubscriptionProfileInterface[]
     */
    protected $profileById = [];

    /**
     * Data object helper.
     *
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * Factory for creating search results.
     *
     * @var SubscriptionProfileSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * Factory for creating profile collections.
     *
     * @var SubscriptionProfileCollectionFactory
     */
    private $subscriptionProfileCollectionFactory;

    /**
     * @var SubscriptionProfileInterfaceFactory
     */
    private $dataSubscriptionProfileFactory;

    /**
     * Factory for creating profiles.
     *
     * @var SubscriptionProfileFactory
     */
    private $subscriptionProfileFactory;

    /**
     * Resource model.
     *
     * @var ResourceSubscriptionProfile
     */
    private $resource;

    /**
     * Entity Manager.
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Repository for saving/retrieving profile addresses.
     *
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * Repository for saving/retrieving profile products.
     *
     * @var ProductSubscriptionProfileRepository
     */
    private $productProfileRepository;


    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * Status history manager.
     *
     * @var HistoryManager
     */
    private $statusHistoryManager;

    /**
     * Status history logger.
     *
     * @var HistoryLogger
     */
    private $statusHistoryLogger;

    /**
     * @param ResourceSubscriptionProfile $resource
     * @param SubscriptionProfileFactory $subscriptionProfileFactory
     * @param SubscriptionProfileInterfaceFactory $dataSubscriptionProfileFactory
     * @param SubscriptionProfileCollectionFactory $subscriptionProfileCollectionFactory
     * @param SubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param EntityManager $entityManager
     * @param AddressRepository $addressRepository
     * @param ProductSubscriptionProfileRepository $productProfileRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param HistoryManager $statusHistoryManager
     * @param HistoryLogger $statusHistoryLogger
     */
    public function __construct(
        ResourceSubscriptionProfile $resource,
        SubscriptionProfileFactory $subscriptionProfileFactory,
        SubscriptionProfileInterfaceFactory $dataSubscriptionProfileFactory,
        SubscriptionProfileCollectionFactory $subscriptionProfileCollectionFactory,
        SubscriptionProfileSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        EntityManager $entityManager,
        AddressRepository $addressRepository,
        ProductSubscriptionProfileRepository $productProfileRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        HistoryManager $statusHistoryManager,
        HistoryLogger $statusHistoryLogger
    ) {
        $this->resource = $resource;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->subscriptionProfileCollectionFactory = $subscriptionProfileCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSubscriptionProfileFactory = $dataSubscriptionProfileFactory;
        $this->entityManager = $entityManager;
        $this->addressRepository = $addressRepository;
        $this->productProfileRepository = $productProfileRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->statusHistoryManager = $statusHistoryManager;
        $this->statusHistoryLogger = $statusHistoryLogger;
    }

    /**
     * Save subscription profile and log status changes if there is.
     *
     * @param SubscriptionProfileInterface $subscriptionProfile
     * @return SubscriptionProfileInterface
     * @throws CouldNotSaveException
     */
    public function save(
        SubscriptionProfileInterface $subscriptionProfile
    ) {
        $oldStatus = $this->statusHistoryManager->getProfileOldStatus($subscriptionProfile);

        try {
            $this->entityManager->save($subscriptionProfile);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscriptionProfile: %1',
                $exception->getMessage()
            ));
        }

        $newStatus = $subscriptionProfile->getStatus();

        // Log status history
        if ($oldStatus != $newStatus) {
            try {
                $this->statusHistoryLogger->log(
                    $subscriptionProfile->getId(),
                    $oldStatus,
                    $newStatus
                );
            } catch (\Exception $exception) {
                throw new CouldNotSaveException(__(
                    'Could not save the subscription profile (ID=%1) status history entry: %2',
                    $subscriptionProfile->getId(),
                    $exception->getMessage()
                ));
            }
        }

        unset($this->profileById[$subscriptionProfile->getId()]);
        return $subscriptionProfile;
    }

    /**
     * @inheritdoc
     */
    public function getById($profileId)
    {
        if (!isset($this->profileById[$profileId])) {
            $subscriptionProfile = $this->subscriptionProfileFactory->create();
            $this->resource->load($subscriptionProfile, $profileId);
            if (!$subscriptionProfile->getId()) {
                throw new NoSuchEntityException(__('SubscriptionProfile with id "%1" does not exist.', $profileId));
            }

            $this->profileById[$profileId] = $subscriptionProfile;
        }

        return $this->profileById[$profileId];
    }

    /**
     * @inheritdoc
     */
    public function getList(
        SearchCriteriaInterface $criteria
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
                SubscriptionProfileInterface::class
            );

            $this->assignProductsAndAddresses($subscriptionProfileData);
            $items[] = $subscriptionProfileData;
        }
        $searchResults->setItems($items);
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(SubscriptionProfileInterface $profile)
    {
        try {
            $this->entityManager->delete($profile);
            unset($this->profileById[$profile->getId()]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SubscriptionProfile: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteById($subscriptionProfileId)
    {
        $this->delete($this->getById($subscriptionProfileId));
    }

    /**
     * After loading profiles via method getList() assigns products and addresses to profile.
     *
     * @param SubscriptionProfileInterface $subscriptionProfileModel
     */
    private function assignProductsAndAddresses(
        SubscriptionProfileInterface $subscriptionProfileModel
    ) {
        $this->criteriaBuilder->addFilter(
            SubscriptionProfileAddressInterface::PROFILE_ID,
            $subscriptionProfileModel->getId()
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $AddressSearchCriteria = $this->criteriaBuilder->create();
        $addresses = $this->addressRepository->getList($AddressSearchCriteria)->getItems();
        $subscriptionProfileModel->setAddresses($addresses);

        $this->criteriaBuilder->addFilter(
            ProductSubscriptionProfileInterface::SUBSCRIPTION_PROFILE_ID,
            $subscriptionProfileModel->getId()
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $productSearchCriteria = $this->criteriaBuilder->create();
        $products = $this->productProfileRepository->getList($productSearchCriteria)->getItems();
        $subscriptionProfileModel->setProducts($products);
    }
}
