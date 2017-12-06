<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status;

use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileStatusHistoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterfaceFactory;

/**
 * Subscription profile status history manager.
 */
class HistoryManager
{
    /**
     * Status history repository.
     *
     * @var SubscriptionProfileStatusHistoryRepositoryInterface
     */
    private $statusHistoryRepository;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * Sort order builder for search criteria.
     *
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * Subscription profile factory.
     *
     * @var SubscriptionProfileInterfaceFactory
     */
    private $profileFactory;

    /**
     * @param SubscriptionProfileStatusHistoryRepositoryInterface $statusHistoryRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param SubscriptionProfileInterfaceFactory $profileFactory
     */
    public function __construct(
        SubscriptionProfileStatusHistoryRepositoryInterface $statusHistoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        SubscriptionProfileInterfaceFactory $profileFactory
    ) {
        $this->statusHistoryRepository = $statusHistoryRepository;
        $this->criteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->profileFactory = $profileFactory;
    }

    /**
     * Get subscription profile status for exact time.
     *
     * @param int|string $subscriptionProfileId
     * @param string $timeString
     * @return null|string
     */
    public function getStatusForTime(
        $subscriptionProfileId,
        $timeString
    ) {
        $result = null;
        if (!empty($timeString)) {
            $time = (new \DateTime($timeString))->getTimestamp();
            $items = $this->getHistoryItems($subscriptionProfileId);
            /** @var SubscriptionProfileStatusHistoryInterface $prev */
            $prev = null;
            /** @var SubscriptionProfileStatusHistoryInterface $item */
            foreach ($items as $item) {
                $timeItem = (new \DateTime($item->getChangedAt()))->getTimestamp();
                if ($timeItem > $time) {
                    if ($prev) {
                        $result = $prev->getStatusNew();
                        $prev = null;
                    }
                    break;
                }
                $prev = $item;
            }
            if ($prev) {
                $result = $prev->getStatusNew();
            }
        }

        return $result;
    }

    /**
     * Returns list of history items in chronological order.
     *
     * @param int|string $subscriptionProfileId
     * @return SubscriptionProfileStatusHistoryInterface[]
     */
    private function getHistoryItems($subscriptionProfileId)
    {
        $this->criteriaBuilder->addFilter(
            SubscriptionProfileStatusHistoryInterface::SUBSCRIPTION_PROFILE_ID,
            $subscriptionProfileId
        );
        $sortOrder = $this->sortOrderBuilder
            ->setField(SubscriptionProfileStatusHistoryInterface::CHANGED_AT)
            ->setAscendingDirection()
            ->create();
        $this->criteriaBuilder->addSortOrder($sortOrder);
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();
        $result = $this->statusHistoryRepository->getList($searchCriteria)->getItems();

        return $result;
    }

    /**
     * Get old status from profile.
     *
     * It try to get from origData, storedData and finally from DB.
     *
     * @param SubscriptionProfileInterface $profile
     * @return int|null
     */
    public function getProfileOldStatus(SubscriptionProfileInterface $profile)
    {
        $result = null;
        if ($profile) {
            $origData = $profile->getOrigData();
            $storedData = $profile->getStoredData();
            if (isset($origData[SubscriptionProfileInterface::STATUS])) {
                $result = $origData[SubscriptionProfileInterface::STATUS];
            } else {
                if (isset($storedData[SubscriptionProfileInterface::STATUS])) {
                    $result = $storedData[SubscriptionProfileInterface::STATUS];
                } else {
                    $result = $this->getProfileStatusFromDb($profile->getId());
                }
            }
        }

        return $result;
    }

    /**
     * Get subscription profile current status from DB.
     *
     * @param int|string $profileId
     * @return null|string
     */
    private function getProfileStatusFromDb($profileId)
    {
        $result = null;
        if ($profileId) {
            /** @var SubscriptionProfileInterface $profile */
            $profile = $this->profileFactory->create();
            $profile->load($profileId);
            if ($profile->getStatus()) {
                $result = $profile->getStatus();
            }
        }

        return $result;
    }
}
