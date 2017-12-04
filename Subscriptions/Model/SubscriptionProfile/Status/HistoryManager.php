<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status;

use TNW\Subscriptions\Api\Data\SubscriptionProfileStatusHistoryInterface;
use TNW\Subscriptions\Api\SubscriptionProfileStatusHistoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;

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
     * @param SubscriptionProfileStatusHistoryRepositoryInterface $statusHistoryRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        SubscriptionProfileStatusHistoryRepositoryInterface $statusHistoryRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->statusHistoryRepository = $statusHistoryRepository;
        $this->criteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
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
}
