<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class RemoveConfigValueGeneratedQuotesCount implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @param ModuleDataSetupInterface $setup
     */
    public function __construct(
        ModuleDataSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    public static function getDependencies()
    {
        return [RemoveConfigValueShippingFallback::class];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->setup->startSetup();

        $configTable = $this->setup->getTable('core_config_data');
        $connection = $this->setup->getConnection();
        $connection->delete(
            $configTable,
            $this->setup->getConnection()->prepareSqlCondition('path', 'tnw_subscriptions_general/advanced/generated_quotes_count')
        );

        $this->setup->endSetup();
    }

    public static function getVersion()
    {
        return '2.2.46';
    }
}
