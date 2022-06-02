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

class FillProfileItemSalesItemTable implements DataPatchInterface, PatchVersionInterface
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
        return [
            UpgradeEntities::class,
            DropProfileAttributes::class,
            AddScheduleAttribute::class,
            UpdateDonationProductAttributes::class,
        ];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->setup->startSetup();

        $connection = $this->setup->getConnection();
        $select = $connection->select()
            ->from(
                ['profileItem' => $this->setup->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                ['profile_item_id' => 'entity_id']
            )
            ->joinInner(
                ['relation' => $this->setup->getTable('tnw_subscriptions_subscription_profile_order')],
                'profileItem.subscription_profile_id = relation.subscription_profile_id',
                []
            )
            ->joinInner(
                ['quoteItem' => $this->setup->getTable('quote_item')],
                'relation.magento_quote_id = quoteItem.quote_id AND profileItem.magento_product_id'
                . ' = quoteItem.product_id AND profileItem.qty = quoteItem.qty',
                ['quote_item_id' => 'item_id']
            )
            ->joinInner(
                ['orderItem' => $this->setup->getTable('sales_order_item')],
                'relation.magento_order_id = orderItem.order_id AND profileItem.magento_product_id'
                . ' = orderItem.product_id AND profileItem.qty = orderItem.qty_ordered',
                ['order_item_id' => 'item_id']
            );

        $query = $connection->insertFromSelect($select, $this->setup->getTable('tnw_subscriptions_profile_item_sales_item'));
        $connection->query($query);

        $this->setup->endSetup();
    }

    public static function getVersion()
    {
        return '2.1.7';
    }
}

