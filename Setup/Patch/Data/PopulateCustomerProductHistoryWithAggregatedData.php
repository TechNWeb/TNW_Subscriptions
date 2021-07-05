<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Exception;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterface;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Class PopulateCustomerProductHistoryWithAggregatedData - populates tnw_subscriptions_customer_product_history table
 * with aggregated data from subscription's tables
 */
class PopulateCustomerProductHistoryWithAggregatedData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * Populates tnw_subscriptions_customer_product_history table with aggregated data from subscription's tables
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()
            ->from(
                ['profileEntity' => $this->moduleDataSetup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY)],
                ['entity_id', 'customer_id']
            )
            ->joinInner(
                ['profileItem' => $this->moduleDataSetup->getTable(ProductSubscriptionProfileInterface::ENTITY_TABLE)],
                'profileEntity.entity_id = profileItem.subscription_profile_id',
                ['magento_product_id']
            );

        try {
            $query = $connection->insertFromSelect(
                $select,
                $this->moduleDataSetup->getTable(CustomerProductHistoryInterface::CUSTOMER_PRODUCT_HISTORY_TABLE),
                ['subscription_profile_id', 'customer_id', 'magento_product_id']
            );
            $connection->query($query);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}
