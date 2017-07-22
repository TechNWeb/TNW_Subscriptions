<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfileOrder;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrderFactory;
use TNW\Subscriptions\Api\SubscriptionProfileOrderRepositoryInterface;

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
     * @var SubscriptionProfileOrderRepositoryInterface
     */
    private $profileOrderRepository;

    /**
     * Manager constructor.
     * @param SubscriptionProfileOrderFactory $profileFactory
     */
    public function __construct(
        SubscriptionProfileOrderFactory $profileFactory,
        SubscriptionProfileOrderRepositoryInterface $profileOrderRepository
    ) {
        $this->profileOrderFactory = $profileFactory;
        $this->profileOrderRepository = $profileOrderRepository;
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
     * @param null $relation
     * @return $this
     */
    public function saveRelation($relation = null)
    {
        if (!$relation){
            $relation = $this->getProfileOrderRelation();
        }

        $this->profileOrderRepository->save($relation);

        return $this;
    }
}