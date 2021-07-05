<?php
/**
 * Copyright © 2020 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Exception;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Class PopulateSalesOrderGridWithSubscriptionProfileIds - populates sales_order_grid table
 * with Subscription Profile Ids
 */
class PopulateSalesOrderGridWithSubscriptionProfileIds implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
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
     * Populate sales_order_grid table with Subscription Profile Ids
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()
            ->from(
                $this->moduleDataSetup->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE),
                [
                    '*',
                    sprintf(
                        'GROUP_CONCAT(%s) AS profile_id',
                        SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID
                    ),
                ]
            )
            ->where(sprintf('%s IS NOT NULL', SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID))
            ->group(SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID)
            ->order(SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID);

        $profileOrders = $connection->fetchAll($select);
        foreach ($profileOrders as $profileOrder) {
            try {
                $connection->update(
                    $this->moduleDataSetup->getTable('sales_order_grid'),
                    ['subscription_profile_id' => $profileOrder['profile_id']],
                    ['entity_id = ?' => $profileOrder['magento_order_id']]
                );
            } catch (Exception $exception) {
                $this->logger->warning($exception->getMessage());
            }
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
