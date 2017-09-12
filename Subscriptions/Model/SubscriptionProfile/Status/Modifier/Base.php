<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ResourceConnection;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Base class for status modifiers.
 */
abstract class Base
{
    /**
     * Config model.
     *
     * @var Config
     */
    protected $config;

    /**
     * Context.
     *
     * @var Context
     */
    protected $context;

    /**
     * Resource connection.
     *
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;


    public function __construct(
        Config $config,
        Context $context,
        ResourceConnection $resource,
        SearchCriteriaBuilder $criteriaBuilder,
        SubscriptionProfileRepository $profileRepository
    ) {
        $this->config = $config;
        $this->context = $context;
        $this->resource = $resource;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->profileRepository = $profileRepository;

    }

    /**
     * Updates profile statuses.
     *
     * @param array $allIds
     */
    public function modify(array $allIds)
    {
        $ids = $this->getIdsToModify($allIds);
        $this->criteriaBuilder->addFilter(
            SubscriptionProfileInterface::ID,
            $ids,
            'in'
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();
        $profiles = $this->profileRepository->getList($searchCriteria)->getItems();
        /** @var SubscriptionProfileInterface $profile */
        foreach ($profiles as $profile) {
            $profile->setStatus($this->getNewStatus());
            $this->profileRepository->save($profile);
        }
    }

    /**
     * Returns list of profile ids to modify.
     *
     * @param array $allIds
     * @return array
     */
    abstract protected function getIdsToModify(array $allIds);

    /**
     * Returns new profile status.
     *
     * @return int
     */
    abstract protected function getNewStatus();
}