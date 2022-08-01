<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use TNW\Subscriptions\Model\ProductBillingFrequency\AvailableSubscriptionProfileGrid;

/**
 * Add Subscription Id to product billing frequency table for generate grid on remove BF from product
 *
 * Class StoreSubscriptionProfileIdsForGrid
 */
class StoreSubscriptionProfileIdsForGrid implements DataPatchInterface, PatchRevertableInterface
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
     * @var AvailableSubscriptionProfileGrid
     */
    private $availableSubscriptionProfileGrid;

    /**
     * StoreSubscriptionProfileIdsForGrid constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AvailableSubscriptionProfileGrid $availableSubscriptionProfileGrid
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AvailableSubscriptionProfileGrid $availableSubscriptionProfileGrid
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->availableSubscriptionProfileGrid = $availableSubscriptionProfileGrid;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [
            AddSubscriptionCanSkipAttribute::class,
            PopulateCustomerProductHistoryWithAggregatedData::class,
            PopulateSalesOrderGridWithSubscriptionProfileIds::class,
            PopulateWebsiteModuleState::class
        ];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return StoreSubscriptionProfileIdsForGrid|void
     * @throws LocalizedException
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('tnw_subscriptions_subscription_profile_entity');
        $select = $connection->select()->from($table)->limit(1);
        $isAvailableProfile = $connection->fetchOne($select);

        if (!empty($isAvailableProfile)) {
            $subscProfiles = $this->subscriptionProfileRepository->getList($this->searchCriteriaBuilder->create())
                ->getItems();
            foreach ($subscProfiles as $subscProfile) {
                $this->availableSubscriptionProfileGrid->saveSubscriptionProfileIdsForFormingGrid($subscProfile);
            }
        }
        $this->moduleDataSetup->endSetup();
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
