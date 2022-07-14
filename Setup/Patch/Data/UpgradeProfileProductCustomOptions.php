<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class UpgradeProfileProductCustomOptions - data patch
 */
class UpgradeProfileProductCustomOptions implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        SerializerInterface $serializer
    ) {
        $this->setup = $setup;
        $this->serializer = $serializer;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [FillProfileItemSalesItemTable::class];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $this->setup->startSetup();

        $productSubscriptionTable = $this->setup->getTable('tnw_subscriptions_product_subscription_profile_entity');

        $connection = $this->setup->getConnection();
        $select = $connection->select()
            ->from($productSubscriptionTable, ['entity_id', 'custom_options'])
            ->where('custom_options IS NOT NULL');

        foreach ($connection->fetchPairs($select) as $entityId => $options) {
            try {
                $options = $this->serializer->unserialize($options);
            } catch (\Exception $e) {
                continue;
            }

            if (isset($options['info_buyRequest'])) {
                continue;
            }

            $connection->update(
                $productSubscriptionTable,
                ['custom_options' => $this->serializer->serialize(
                    ['info_buyRequest' => ['super_attribute' => $options]]
                )],
                $connection->prepareSqlCondition('entity_id', $entityId)
            );
        }

        $this->setup->endSetup();
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '2.1.15';
    }
}
