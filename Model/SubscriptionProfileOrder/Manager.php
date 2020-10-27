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
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface as RelationRepository;
use TNW\Subscriptions\Model\SubscriptionProfileOrderFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\Config;

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
     * Manager constructor.
     * @param SubscriptionProfileOrderFactory $profileFactory
     * @param RelationRepository $profileOrderRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param Config $config
     */
    public function __construct(
        SubscriptionProfileOrderFactory $profileFactory,
        RelationRepository $profileOrderRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        Config $config
    ) {
        $this->profileOrderFactory = $profileFactory;
        $this->profileOrderRepository = $profileOrderRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->config = $config;
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
     * @param null|SubscriptionProfileOrderInterface $relation
     * @return SubscriptionProfileOrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveRelation($relation = null)
    {
        if (!$relation) {
            $relation = $this->getProfileOrderRelation();
        }

        $relation = $this->profileOrderRepository->save($relation);

        return $relation;
    }

    /**
     * Returns list of all profile relations.
     *
     * @param int $profileId
     * @return SubscriptionProfileOrderInterface[]
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
     * Retrieve next Subscription profile order
     *
     * @param SubscriptionProfileInterface $profile
     * @param null|bool $all
     * @return false|SubscriptionProfileOrderInterface|SubscriptionProfileOrderInterface[]
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
     * @throws \Magento\Framework\Exception\LocalizedException
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
