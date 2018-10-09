<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron\Quote;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface as SubscriptionProduct;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Collection;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ProcessInterface;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Base
 */
abstract class Base implements ProcessInterface
{
    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    protected $profileRepository;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * Subscriptions context.
     *
     * @var Context
     */
    protected $context;

    /**
     * Subscriptions config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Repository fore saving/retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Factory for creating subscription collection.
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Profile relation manager.
     *
     * @var RelationManager
     */
    protected $relationManager;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $profileManager;

    /**
     * @param SubscriptionProfileRepository $profileRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param Context $context
     * @param Config $config
     * @param CartRepositoryInterface $cartRepository
     * @param CollectionFactory $collectionFactory
     * @param RelationManager $relationManager
     */
    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        Context $context,
        Config $config,
        CartRepositoryInterface $cartRepository,
        CollectionFactory $collectionFactory,
        RelationManager $relationManager,
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager
    ) {
        $this->profileRepository = $profileRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->context = $context;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
        $this->collectionFactory = $collectionFactory;
        $this->relationManager = $relationManager;
        $this->profileManager = $profileManager;
    }

    /**
     * Returns list of profiles ids to process.
     *
     * @param int $websiteId
     * @return array
     */
    abstract public function getProfilesIdsToProcess($websiteId);

    /**
     * Returns base collection.
     *
     * @return Collection
     */
    protected function getBaseCollection()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->joinTable(
            ['products' => $collection->getTable(SubscriptionProduct::ENTITY_TABLE)],
            SubscriptionProduct::SUBSCRIPTION_PROFILE_ID . ' = ' . SubscriptionProfileInterface::ID,
            ['products_need_recollect' => SubscriptionProduct::NEED_RECOLLECT]
        )->groupByAttribute(
            SubscriptionProfileInterface::ID
        );

        return $collection;
    }

    /**
     * Returns list of profiles with no quotes.
     *
     * @param int $websiteId
     *
     * @return SubscriptionProfileInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProfiles($websiteId)
    {
        $result = [];
        $ids = $this->getProfilesIdsToProcess($websiteId);
        if (!empty($ids)) {
            $searchCriteria = $this->criteriaBuilder
                ->addFilter(SubscriptionProfileInterface::ID, $ids, 'in')
                ->create();

            $result = $this->profileRepository
                ->getList($searchCriteria)
                ->getItems();
        }

        return $result;
    }

    /**
     * Updates quote for profile.
     *
     * @param SubscriptionProfileInterface $profile
     * @param Quote $quote
     * @return Quote
     */
    protected function processQuote(SubscriptionProfileInterface $profile, Quote $quote)
    {
        $this->profileManager->populateQuoteData($quote, $profile);
        $this->cartRepository->save($quote);

        return $quote;
    }
}
