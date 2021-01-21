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
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\AddressRepository;
use TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManagerFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\HistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\HistoryManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;

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
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @var EmailNotifierFactory
     */
    private $emailNotifierFactory;

    /**
     * @var BillingCyclesManagerFactory
     */
    private $billingCyclesManagerFactory;

    /**
     * @var bool
     */
    private $isAutomated = false;

    /**
     * @var ProfileOrderManager
     */
    private $profileOrderManager;

    /**
     * SubscriptionProfileRepository constructor.
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
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param EmailNotifierFactory $emailNotifierFactory
     * @param BillingCyclesManagerFactory $billingCyclesManagerFactory
     * @param ProfileOrderManager $profileOrderManager
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
        HistoryLogger $statusHistoryLogger,
        MessageHistoryLogger $messageHistoryLogger,
        EmailNotifierFactory $emailNotifierFactory,
        BillingCyclesManagerFactory $billingCyclesManagerFactory,
        ProfileOrderManager $profileOrderManager
    ) {
        $this->profileOrderManager = $profileOrderManager;
        $this->billingCyclesManagerFactory = $billingCyclesManagerFactory;
        $this->messageHistoryLogger = $messageHistoryLogger;
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
        $this->emailNotifierFactory = $emailNotifierFactory;
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
        $oldConfigOption = $this->statusHistoryManager->getProfileOldConfigOption($subscriptionProfile);
        $oldPaymentData =  $this->statusHistoryManager->getProfileOldPaymentData($subscriptionProfile);

        try {
            $this->entityManager->save($subscriptionProfile);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the subscriptionProfile: %1',
                $exception->getMessage()
            ), $exception);
        }

        $newConfigOption = '';
        $parentProductName = '';
        foreach ($subscriptionProfile->getProducts() as $product) {
            if ($product->getParentId()) {
                $newConfigOption = $product->getName();
            } else {
                $parentProductName = $product->getName();
            }
        }
        if ($newConfigOption && $parentProductName && $oldConfigOption != $newConfigOption) {
            $this->messageHistoryLogger->message(
                MessageHistoryLogger::MESSAGE_SUBSCRIPTION_PRODUCT_CHANGED,
                [
                    $parentProductName . ' ' . $oldConfigOption,
                    $parentProductName . ' ' . $newConfigOption
                ],
                $subscriptionProfile->getId()
            );
        }

        $newStatus = $subscriptionProfile->getStatus();

        // Log status history
        if ($oldStatus != $newStatus) {
            $this->processBillingCyclesOnStatusChange($newStatus, $oldStatus, $subscriptionProfile);
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
            if ($oldStatus) {
                try {
                    $this->emailNotifierFactory->create()->profileStatusChange(
                        $subscriptionProfile,
                        $oldStatus,
                        $newStatus
                    );
                } catch (\Exception $exception) {
                    //TODO: add this to log, as it should not prevent the current process
                }
            }
        }
        if (
            isset($oldPaymentData['engine_code'])
            && $oldPaymentData['payment_additional_info']
            && $subscriptionProfile->getPayment()->getEngineCode() == $oldPaymentData['engine_code']
            && $subscriptionProfile->getPayment()->getPaymentAdditionalInfo()
                != $oldPaymentData['payment_additional_info']
            && $subscriptionProfile->getPayment()->getEngineCode() == 'purchaseorder'
        ) {
            $oldPO = json_decode($oldPaymentData['payment_additional_info'], true);
            $oldPO = $oldPO['po_number'];
            $newPO = json_decode($subscriptionProfile->getPayment()->getPaymentAdditionalInfo(), true);
            $newPO = $newPO['po_number'];
            $this->messageHistoryLogger->message(
                MessageHistoryLogger::PAYMENT_METHOD_DATA_CHANGED,
                [
                    __('Purchase Order Number'),
                    $oldPO,
                    $newPO
                ],
                $subscriptionProfile->getId()
            );
        }

        unset($this->profileById[$subscriptionProfile->getId()]);
        return $subscriptionProfile;
    }

    /**
     * @param $isAutomated
     * @return $this
     */
    public function setAutomatedProcessFlag($isAutomated)
    {
        $this->isAutomated = (bool) $isAutomated;
        return $this;
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

    /**
     * @param $newStatus
     * @param $oldStatus
     * @param $subscriptionProfile
     * @return $this
     * @throws \Exception
     */
    private function processBillingCyclesOnStatusChange($newStatus, $oldStatus, $subscriptionProfile)
    {
        if ($newStatus == ProfileStatus::STATUS_ACTIVE
            && !$this->isAutomated
        ) {
            $this->processSaveRelation(0, $subscriptionProfile, 1);
        }
        return $this;
    }

    /**
     * @param $cycleNumberToSchedule
     * @param $subscriptionProfile
     * @param $cyclesToSkip
     * @return bool
     * @throws \Exception
     */
    private function processSaveRelation($cycleNumberToSchedule, $subscriptionProfile, $cyclesToSkip)
    {
        $nextProfileRelation = $this->profileOrderManager->getNextProfileRelation($subscriptionProfile);
        $rescheduled = false;
        list($cycles, $needMore, $existingCycles) = $this->billingCyclesManagerFactory->create()->getBillingCycles(
            $subscriptionProfile,
            $cyclesToSkip,
            true,
            true
        );
        if ($cycles && is_array($cycles) && array_key_exists($cycleNumberToSchedule, $cycles)) {
            $nextProfileRelation->setScheduledAt($cycles[$cycleNumberToSchedule]);
            $rescheduled = true;
            try {
                $this->profileOrderManager->saveRelation($nextProfileRelation);
            } catch (\Exception $e) {
                $rescheduled = false;
            }
        }
        if (!$rescheduled) {
            $this->messageHistoryLogger->log(
                __(
                    'Profile won`t process anymore - paused for billing cycles exceeds '
                    . 'the possible payments/orders limit.'
                ),
                $subscriptionProfile->getId(),
                true,
                true,
                $this->isAutomated
            );
        }
        return $rescheduled;
    }
}
