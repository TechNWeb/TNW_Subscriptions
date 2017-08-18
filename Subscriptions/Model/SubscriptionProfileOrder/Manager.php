<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfileOrder;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
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
     * Manager constructor.
     * @param SubscriptionProfileOrderFactory $profileFactory
     * @param RelationRepository $profileOrderRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        SubscriptionProfileOrderFactory $profileFactory,
        RelationRepository $profileOrderRepository,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->profileOrderFactory = $profileFactory;
        $this->profileOrderRepository = $profileOrderRepository;
        $this->criteriaBuilder = $criteriaBuilder;
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
            $this->profileOrderRelation = $this->getNewProfileOrderReletion();
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
    public function getNewProfileOrderReletion()
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
}