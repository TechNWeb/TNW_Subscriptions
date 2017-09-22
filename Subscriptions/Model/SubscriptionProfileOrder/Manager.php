<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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

/**
 * Class Manager
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
     * Manager constructor.
     * @param SubscriptionProfileOrderFactory $profileFactory
     * @param RelationRepository $profileOrderRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        SubscriptionProfileOrderFactory $profileFactory,
        RelationRepository $profileOrderRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->profileOrderFactory = $profileFactory;
        $this->profileOrderRepository = $profileOrderRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }


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
     * @return null|SubscriptionProfileOrderInterface
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
            SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID, $profileId
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
     */
    public function getRelationById($id)
    {
        return $this->profileOrderRepository->getById($id);
    }

    /**
     * Retrieve next Subscription profile order
     *
     * @param SubscriptionProfileInterface $profile
     * @return false|SubscriptionProfileOrderInterface
     */
    public function getNextProfileRelation($profile)
    {
        $result = null;
        /** @var \Magento\Framework\Api\SortOrder $sortOrder */
        $sortOrder = $this->sortOrderBuilder
            ->setField(SubscriptionProfileOrderInterface::SCHEDULED_AT)
            ->setDirection(SortOrder::SORT_ASC)
            ->create();
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder
            ->addFilter(SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID, $profile->getId())
            ->addFilter(SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID, null, 'null' )
            ->addFilter(SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID, null, 'notnull' )
            ->setSortOrders([$sortOrder])
            ->setPageSize(1)
            ->create();
        $results =  $this->profileOrderRepository->getList($searchCriteria)->getItems();
        if (count($results)) {
            $result = reset($results);
        }

        return $result;
    }
}