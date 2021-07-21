<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use TNW\Subscriptions\Model\ProductBillingFrequency\AvailableGridSubscriptionProfile;

/**
 * Add Subscription Id to product billing frequency table for generate grid on remove BF from product
 *
 * Class AddSubscIdForCreateGrid
 * @package TNW\Subscriptions\Setup\Patch\Data
 */
class AddSubscIdForCreateGrid implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AvailableGridSubscriptionProfile
     */
    private $availableGridSubscriptionProfile;

    /**
     * AddSubscIdForCreateGrid constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AvailableGridSubscriptionProfile $availableGridSubscriptionProfile
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AvailableGridSubscriptionProfile $availableGridSubscriptionProfile
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->availableGridSubscriptionProfile = $availableGridSubscriptionProfile;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return AddSubscIdForCreateGrid|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $subscProfiles = $this->subscriptionProfileRepository->getList($this->searchCriteriaBuilder->create())
            ->getItems();
        foreach ($subscProfiles as $subscProfile) {
            $this->availableGridSubscriptionProfile->getDataForUrl($subscProfile);
        }
        $this->moduleDataSetup->endSetup();
    }
}
