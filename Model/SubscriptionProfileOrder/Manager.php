<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfileOrder;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface as RelationRepository;
use TNW\Subscriptions\Model\SubscriptionProfileOrderFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\Config;
use Magento\Sales\Model\AdminOrder\EmailSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterfaceFactory;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderSearchResultsInterfaceFactory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder as ResourceSubscriptionProfileOrder;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class Manager - for subscription profile order
 */
class Manager
{
    /**
     * Factory for creating subscription profile product.
     *
     * @var SubscriptionProfileOrderFactory
     */
    private $profileOrderFactory;

    /**
     * Subscription profile to order relation.
     *
     * @var SubscriptionProfileOrderInterface
     */
    private $profileOrderRelation;

    /**
     * Repository for saving/retrieving profile to order relations.
     *
     * @var RelationRepository
     */
    private $profileOrderRepository;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * Subscription config.
     *
     * @var Config
     */
    private $config;

    /**
     * @var EmailSender
     */
    private $sender;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var ResourceSubscriptionProfileOrder
     */
    private $resource;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * Manager constructor.
     * @param SubscriptionProfileOrderFactory $profileFactory
     * @param RelationRepository $profileOrderRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param Config $config
     * @param EmailSender $sender
     * @param OrderRepositoryInterface $orderRepository
     * @param SubscriptionProfileRepositoryInterface $profileRepository
     * @param ResourceSubscriptionProfileOrder $resource
     * @param Json $serializer
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        SubscriptionProfileOrderFactory $profileFactory,
        RelationRepository $profileOrderRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        Config $config,
        EmailSender $sender,
        OrderRepositoryInterface $orderRepository,
        SubscriptionProfileRepositoryInterface $profileRepository,
        ResourceSubscriptionProfileOrder $resource,
        Json $serializer,
        LoggerInterface $logger = null
    ) {
        $this->serializer = $serializer;
        $this->resource = $resource;
        $this->profileRepository = $profileRepository;
        $this->logger = $logger;
        $this->profileOrderFactory = $profileFactory;
        $this->profileOrderRepository = $profileOrderRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->config = $config;
        $this->sender = $sender;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->profileOrderRelation = null;
        return $this;
    }

    /**
     * Returns current/new profile to order relation.
     *
     * @return SubscriptionProfileOrderInterface
     */
    public function getProfileOrderRelation()
    {
        if (!$this->profileOrderRelation) {
            $this->profileOrderRelation = $this->getNewProfileOrderRelation();
        }

        return $this->profileOrderRelation;
    }

    /**
     * Sets profile to order relation.
     *
     * @param SubscriptionProfileOrderInterface $profileOrder
     */
    public function setProfileOrderRelation(
        SubscriptionProfileOrderInterface $profileOrder
    ) {
        $this->profileOrderRelation = $profileOrder;
    }

    /**
     * Returns empty profile to order relation.
     *
     * @return SubscriptionProfileOrderInterface
     */
    public function getNewProfileOrderRelation()
    {
        return $this->profileOrderFactory->create();
    }

    /**
     * Saves profile to order relation.
     *
     * @param SubscriptionProfileOrderInterface|null $relation
     * @return SubscriptionProfileOrderInterface
     * @throws LocalizedException
     */
    public function saveRelation($relation = null)
    {
        if (!$relation) {
            $relation = $this->getProfileOrderRelation();
        }

        $relation = $this->profileOrderRepository->save($relation);
        $magentoOrderId = $relation->getMagentoOrderId();
        if ($magentoOrderId) {
            try {
                $profileIds = $this->resource->getProfileIdsByMagentoOrderId((int) $magentoOrderId);
                $subscriptionProfile = $this->profileRepository->getById(
                    $relation->getSubscriptionProfileId()
                );
                $profileOrders = $this->resource->getProfileOrdersByProfileId(
                    $subscriptionProfile->getId()
                );
                $installRecurringData = $this->getRecurringInstallmentData(
                    $profileOrders,
                    $subscriptionProfile,
                    $profileIds
                );

                if ($profileIds) {
                    $this->resource->populateSalesOrderGridWithProfileIds((int) $magentoOrderId, $profileIds);
                    $this->resource->populateRecurringInstallmentData(
                        $magentoOrderId,
                        $installRecurringData['paidRecurring'],
                        $installRecurringData['finalRecurring'],
                        $installRecurringData['firstRecurring'],
                        $installRecurringData['expirationCc'],
                        $installRecurringData['staticTotalBillingCycles']
                    );
                    $this->resource->populateRecurringInstallmentDataSalesOrder(
                        $magentoOrderId,
                        $installRecurringData['paidRecurring'],
                        $installRecurringData['finalRecurring'],
                        $installRecurringData['firstRecurring'],
                        $installRecurringData['expirationCc'],
                        $installRecurringData['staticTotalBillingCycles']
                    );
                }
            } catch (Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }

        if (array_key_exists('magento_order_id', $relation->getData()) && $relation->getMagentoOrderId()) {
            if (!$relation->getData('email_sent')) {
                $this->sender->send($this->orderRepository->get(
                    $relation->getMagentoOrderId()
                ));
            }
        }

        return $relation;
    }

    /**
     * @param $profileId
     * @return SubscriptionProfileOrderInterface[]
     * @throws LocalizedException
     */
    public function getAllProfileRelations($profileId)
    {
        $this->criteriaBuilder->addFilter(
            SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID,
            $profileId
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();

        return $this->profileOrderRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param $profileId
     * @return false|mixed|SubscriptionProfileOrderInterface|null
     * @throws LocalizedException
     */
    public function getLastSuccessfulProfileRelation($profileId)
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField(SubscriptionProfileOrderInterface::SCHEDULED_AT)
            ->setDescendingDirection()
            ->create();

        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->addFilter(
            SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID,
            $profileId
        )->addFilter(
            SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
            null,
            'notnull'
        )->addSortOrder($sortOrder)->setPageSize(1)->create();

        $relations = $this->profileOrderRepository->getList($searchCriteria)->getItems();
        return is_array($relations) && count($relations) ? reset($relations) : null;
    }

    /**
     * Returns profile relation by id.
     *
     * @param int $id
     * @return SubscriptionProfileOrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRelationById($id)
    {
        return $this->profileOrderRepository->getById($id);
    }

    /**
     * @param SubscriptionProfileInterface $profile
     * @param null $all
     * @return bool|mixed|SubscriptionProfileOrderInterface|SubscriptionProfileOrderInterface[]
     * @throws LocalizedException
     */
    public function getNextProfileRelation(SubscriptionProfileInterface $profile, $all = null)
    {
        $result = false;
        /** @var \Magento\Framework\Api\SortOrder $sortOrder */
        $sortOrder = $this->sortOrderBuilder
            ->setField(SubscriptionProfileOrderInterface::SCHEDULED_AT)
            ->setDirection(SortOrder::SORT_ASC)
            ->create();
        $this->criteriaBuilder
            ->addFilter(SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID, $profile->getId())
            ->addFilter(SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID, null, 'null')
            ->addFilter(SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID, null, 'notnull')
            ->setSortOrders([$sortOrder]);
        if (!$all) {
            $this->criteriaBuilder->setPageSize(1);
        }
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();

        $results = $this->profileOrderRepository->getList($searchCriteria)->getItems();
        if (count($results)) {
            $result = $all ? $results : reset($results);
        }

        return $result;
    }

    /**
     * Retrieve Subscription profile ID by Order ID
     *
     * @param int $orderId
     *
     * @return int[]
     * @throws LocalizedException
     */
    public function getProfileIdsByOrder($orderId)
    {
        $searchCriteria = $this->criteriaBuilder
            ->addFilter(SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID, $orderId)
            ->create();

        $searchResults = $this->profileOrderRepository->getList($searchCriteria);
        return array_map(function (SubscriptionProfileOrderInterface $profileOrder) {
            return $profileOrder->getSubscriptionProfileId();
        }, $searchResults->getItems());
    }

    /**
     * Retrieve status message.
     *
     * @param int|string $status
     * @param string $scheduledAt
     * @return string
     */
    public function getStatusMessage($status, $scheduledAt)
    {
        $result = '';
        switch ($status) {
            case ProfileStatus::STATUS_ACTIVE:
                $result = __('Subscription is current');
                break;
            case ProfileStatus::STATUS_HOLDED:
                $result = __('Subscription is inactive');
                break;
            case ProfileStatus::STATUS_TRIAL:
                $result = __('In trial period');
                break;
            case ProfileStatus::STATUS_PENDING:
                $result = __('Awaiting payment');
                break;
            case ProfileStatus::STATUS_COMPLETE:
                $result = __('Subscription successfully completed');
                break;
            case ProfileStatus::STATUS_SUSPENDED:
                $days = $this->getDaysPastDue($scheduledAt);
                $result = __('%1 day%2 past due!', $days, $days !== 1 ? 's' : '');
                break;
            case ProfileStatus::STATUS_CANCELED:
                $result = __('Subscription is canceled');
                break;
            case ProfileStatus::STATUS_PAST_DUE:
                $days = $this->getDaysUntilSuspended($scheduledAt);
                $result = __(
                    '%1 day%2 until suspended',
                    $days,
                    $days !== 1 ? 's' : ''
                );
                break;
        }

        return $result;
    }

    /**
     * Update scheduled_at date for profile order
     *
     * @param SubscriptionProfileInterface $profile
     * @param string $date
     */
    public function updateNextPaymentDate(SubscriptionProfileInterface $profile, string $date)
    {
        $next = $this->getNextProfileRelation($profile);
        $next->setScheduledAt($date)->save();
    }

    /**
     * Get data for recurring installment columns
     *
     * @param $profileOrders
     * @param $subscriptionProfile
     * @param $profileIds
     * @return array
     */
    private function getRecurringInstallmentData($profileOrders, $subscriptionProfile, $profileIds)
    {
        $result = [];
        $profileStatus = $subscriptionProfile->getStatus();
        $staticTotalBillingCycles = $subscriptionProfile->getStaticTotalBillingCycles();
        if (
            $profileStatus !== ProfileStatus::STATUS_COMPLETE
            || $profileStatus !== ProfileStatus::STATUS_CANCELED
        ) {
            if (strpos($profileIds, ',') !== false) {
                $result = $this->getInstallmentDataForMultipleProfiles($profileIds);
            } else {
                $result = $this->getInstallmentDataForSingleProfile(
                    $profileOrders,
                    $subscriptionProfile,
                    $staticTotalBillingCycles
                );
            }
            $result['staticTotalBillingCycles'] = $staticTotalBillingCycles;
        }

        return $result;
    }

    /**
     * @param $profileIds
     * @return array
     */
    private function getInstallmentDataForMultipleProfiles($profileIds)
    {
        $profileIds = explode(',', $profileIds);
        $paid = [];
        $firstRecurring = [];
        $finalRecurring = [];
        $ccExpiration = [];
        foreach ($profileIds as $profileId) {
            try {
                $profile = $this->profileRepository->getById($profileId);
                $profileOrders = $this->resource->getProfileOrdersByProfileId(
                    $profileId
                );
            } catch (NoSuchEntityException $e) {
                $profile = null;
                $profileOrders = null;
                $this->logger->error($e->getMessage());
            }
            $static = $profile->getStaticTotalBillingCycles();
            if (isset($static)) {
                $paid[] = implode(",", [count($profileOrders), $static]);
            } else {
                $paid[] = implode(",", [count($profileOrders)]);
            }
            $firstRecurring[] = $profile->getStartDate();
            $ccFinal = $profile->getFinalDateForInstallmentData($profile, $profileOrders);
            $ccExpiration[] = $profile->getCcEcpirationStatus($profile, $ccFinal);
            $finalRecurring[] = $ccFinal;
        }

        return [
            'paidRecurring' => $this->serializer->serialize($paid),
            'firstRecurring' => $this->serializer->serialize($firstRecurring),
            'finalRecurring' => $this->serializer->serialize($finalRecurring),
            'expirationCc' => $this->serializer->serialize($ccExpiration),
        ];
    }

    /**
     * @param $profileOrders
     * @param SubscriptionProfile $subscriptionProfile
     * @param $staticTotalBillingCycles
     * @return array
     */
    private function getInstallmentDataForSingleProfile(
        $profileOrders,
        $subscriptionProfile,
        $staticTotalBillingCycles
    ) {
        if ($staticTotalBillingCycles !== null && $staticTotalBillingCycles > 1) {
            $result['paidRecurring'] = implode(",", [count($profileOrders), $staticTotalBillingCycles]);
        } else {
            $result['paidRecurring'] = implode(",", [count($profileOrders)]);
        }
        $result['firstRecurring'] = $subscriptionProfile->getStartDate();
        $result['finalRecurring'] = $subscriptionProfile
            ->getFinalDateForInstallmentData($subscriptionProfile, $profileOrders);
        $result['expirationCc'] = $subscriptionProfile
            ->getCcEcpirationStatus($subscriptionProfile, $result['finalRecurring']);
        return $result;
    }

    /**
     * Retrieve the number of days from the last successful payment.
     *
     * @param string $scheduledAt
     * @return int
     */
    private function getDaysPastDue($scheduledAt)
    {
        if (!$scheduledAt) {
            return 0;
        }

        $dateFrom = new \DateTime($scheduledAt);
        $dateTo = new \DateTime();
        if ($dateFrom > $dateTo) {
            return 0;
        }

        return $dateFrom->diff($dateTo)->days;
    }

    /**
     * Retrieve count of days from when the payment was due until today.
     *
     * @param string $scheduledAt
     * @return int
     */
    private function getDaysUntilSuspended($scheduledAt)
    {
        $result = 0;
        if ($scheduledAt) {
            $period = min([
                (int)$this->config->getGracePeriod(),
                (int)$this->config->getAttemptCount() * (int)$this->config->getAttemptInterval(),
            ]);
            $beginPeriod = new \DateTime($scheduledAt . " +$period days");
            $dayDateDiff = $beginPeriod->diff(new \DateTime())->days;
            $result = ($dayDateDiff > 0) ? $dayDateDiff : 0;
        }

        return $result;
    }
}
