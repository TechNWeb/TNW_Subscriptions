<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderSearchResultsInterfaceFactory;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder as ResourceSubscriptionProfileOrder;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder\CollectionFactory
    as SubscriptionProfileOrderCollectionFactory;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use TNW\Subscriptions\Model\BillingFrequencyRepository;

/**
 * Class SubscriptionProfileOrderRepository - repository object for subscription profile orders
 */
class SubscriptionProfileOrderRepository implements SubscriptionProfileOrderRepositoryInterface
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SubscriptionProfileOrderSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var SubscriptionProfileOrderFactory
     */
    private $subscriptionProfileOrderFactory;

    /**
     * @var SubscriptionProfileOrderInterfaceFactory
     */
    private $dataSubscriptionProfileOrderFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ResourceSubscriptionProfileOrder
     */
    private $resource;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var SubscriptionProfileOrderCollectionFactory
     */
    private $subscriptionProfileOrderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var BillingFrequencyRepository
     */
    private $billingFrequencyRepository;

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
        StoreManagerInterface $storeManager,
        SubscriptionProfileRepositoryInterface $profileRepository,
        TimezoneInterface $timezone,
        BillingFrequencyRepository $billingFrequencyRepository,
        LoggerInterface $logger = null
    ) {
        $this->resource = $resource;
        $this->subscriptionProfileOrderFactory = $subscriptionProfileOrderFactory;
        $this->subscriptionProfileOrderCollectionFactory = $subscriptionProfileOrderCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSubscriptionProfileOrderFactory = $dataSubscriptionProfileOrderFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->profileRepository = $profileRepository;
        $this->timezone = $timezone;
        $this->billingFrequencyRepository = $billingFrequencyRepository;
        $objectManager = ObjectManager::getInstance();
        $this->logger = $logger ?: $objectManager->get(LoggerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface $subscriptionProfileOrder
    ) {
        try {
            $this->resource->save($subscriptionProfileOrder);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscriptionProfileOrder: %1',
                $exception->getMessage()
            ));
        }
        $magentoOrderId = $subscriptionProfileOrder->getMagentoOrderId();
        if ($magentoOrderId) {
            try {
                $profileIds = $this->resource->getProfileIdsByMagentoOrderId((int)$magentoOrderId);

                $subscriptionProfile = $this->profileRepository->getById(
                    $subscriptionProfileOrder->getSubscriptionProfileId()
                );
                $expirationCc = $this->resource->getCcEcpiration($subscriptionProfile);
                $firstRecurring = $this->timezone->formatDate(
                    $subscriptionProfile->getStartDate(),
                    \IntlDateFormatter::MEDIUM
                );
                $startDate = $subscriptionProfile->getCreatedAt();
                $billingFrequency = $this->billingFrequencyRepository->getById(
                    $subscriptionProfile->getBillingFrequencyId()
                );
                $finalRecurring = $this->timezone->formatDate(
                    $this->resource->getFinalDate(
                    $billingFrequency,
                    $subscriptionProfile->getTotalBillingCycles(),
                    $startDate,
                    $subscriptionProfile->getTerm(),
                ),
                    \IntlDateFormatter::MEDIUM
                );
                $paidRecurring = " / ∞";

                if ($profileIds) {
                    $this->resource->populateSalesOrderGridWithProfileIds((int)$magentoOrderId, $profileIds);
                    $this->resource->populateRecurringInstallmentData(
                        $magentoOrderId,
                        $paidRecurring,
                        $finalRecurring,
                        $firstRecurring,
                        $expirationCc
                    );
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
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
            throw new NoSuchEntityException(__(
                'SubscriptionProfileOrder with id "%1" does not exist.',
                $subscriptionProfileOrderId
            ));
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
                \TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface::class
            );
            $items[] = $subscriptionProfileOrderData;
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

    /**
     * @param $profileId
     * @return array
     */
    public function getLastSubscriptionOrderByProfileId($profileId)
    {
        return $this->resource->getLastProfileOrderByProfileId((int) $profileId);
    }
}
