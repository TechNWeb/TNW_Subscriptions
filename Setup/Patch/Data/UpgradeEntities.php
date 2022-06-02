<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Setup\SubscriptionSetup;
use TNW\Subscriptions\Setup\SubscriptionSetupFactory;

class UpgradeEntities implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var SubscriptionSetupFactory
     */
    private $subscriptionSetupFactory;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param SubscriptionSetupFactory $subscriptionSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        SubscriptionSetupFactory $subscriptionSetupFactory
    ) {
        $this->setup = $setup;
        $this->subscriptionSetupFactory = $subscriptionSetupFactory;
    }

    public static function getDependencies()
    {
        return [RemoveRequiredFlagFromProductAttributes::class];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->setup->startSetup();

        /** @var SubscriptionSetup $subscriptionSetup */
        $subscriptionSetup = $this->subscriptionSetupFactory->create(['setup' => $this->setup]);

        $entityTypeId = $subscriptionSetup->getEntityTypeId(ProductSubscriptionProfile::ENTITY);
        $select = $this->setup->getConnection()
            ->select()
            ->from($this->setup->getTable('eav_attribute'), ['attribute_id'])
            ->where($this->setup->getConnection()->prepareSqlCondition('entity_type_id', $entityTypeId));

        $query = $this->setup->getConnection()
            ->insertFromSelect(
                $select,
                $this->setup->getTable('tnw_subscriptions_product_subscription_profile_eav_attribute'),
                ['attribute_id'],
                AdapterInterface::INSERT_IGNORE
            );

        $this->setup->getConnection()->query($query);

        $subscriptionSetup->installEntities();

        $this->setup->endSetup();
    }

    public static function getVersion()
    {
        return '2.1.0';
    }
}
