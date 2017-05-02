<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $tablesToCreate = [];

        $tableName = 'tnw_subscriptions_billing_frequency';

        if (!$setup->tableExists($setup->getTable($tableName))) {

            $tableTnwBillingFrequency = $setup->getConnection()->newTable($setup->getTable($tableName));

            $tableTnwBillingFrequency->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
                'Entity ID'
            )->addColumn(
                'unit',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'unit'
            )->addColumn(
                'website_id',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'website_id'
            )->addColumn(
                'label',
                Table::TYPE_TEXT,
                512,
                ['nullable' => false],
                'label'
            )->addColumn(
                'status',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'status'
            )->addColumn(
                'frequency',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'frequency'
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                'website_id',
                'store_website',
                'website_id'
                ),
                'website_id',
                'store_website',
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

            $tablesToCreate[] = $tableTnwBillingFrequency;
        }

        $tableName = 'tnw_subscriptions_product_billing_frequency';

        if (!$setup->tableExists($setup->getTable($tableName))) {

            $tableTnwProductBillingFrequency = $setup->getConnection()->newTable($setup->getTable($tableName));

            $tableTnwProductBillingFrequency->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
                'Entity ID'
            )->addColumn(
                'billing_frequency_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'billing_frequency_id'
            )->addColumn(
                'magento_product_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'magento_product_id'
            )->addColumn(
                'default_billing_frequency',
                Table::TYPE_BOOLEAN,
                null,
                ['default' => '0', 'nullable' => false],
                'default_billing_frequency'
            )->addColumn(
                'price',
                Table::TYPE_DECIMAL,
                12,
                ['nullable' => false, 'precision' => 2],
                'price'
            )->addColumn(
                'initial_fee',
                Table::TYPE_DECIMAL,
                12,
                ['nullable' => false, 'precision' => 2],
                'initial_fee'
            )->addIndex(
                $installer->getIdxName($tableName,
                    ['billing_frequency_id', 'magento_product_id']),
                ['billing_frequency_id', 'magento_product_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'billing_frequency_id',
                    'tnw_subscriptions_billing_frequency',
                    'id'
                ),
                'billing_frequency_id',
                'tnw_subscriptions_billing_frequency',
                'id',
                Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'magento_product_id',
                    'catalog_product_entity',
                    'entity_id'
                ),
                'magento_product_id',
                'catalog_product_entity',
                'entity_id',
                Table::ACTION_CASCADE
            );

            $tablesToCreate[] = $tableTnwProductBillingFrequency;
        }

        $tableName = 'tnw_subscriptions_subscription_profile';

        if (!$setup->tableExists($setup->getTable($tableName))) {

            $tableTnwSubscriptionProfile = $setup->getConnection()->newTable($setup->getTable($tableName));

            $tableTnwSubscriptionProfile->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
                'Entity ID'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'customer_id'
            )->addColumn(
                'billing_frequency_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'billing_frequency_id'
            )->addColumn(
                'label',
                Table::TYPE_TEXT,
                512,
                ['nullable' => false],
                'label'
            )->addColumn(
                'unit',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'unit'
            )->addColumn(
                'website_id',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'website_id'
            )->addColumn(
                'status',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'status'
            )->addColumn(
                'frequency',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'frequency'
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'customer_id',
                    'customer_entity',
                    'entity_id'
                ),
                'customer_id',
                'customer_entity',
                'entity_id'
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'billing_frequency_id',
                    'tnw_subscriptions_billing_frequency',
                    'id'
                ),
                'billing_frequency_id',
                'tnw_subscriptions_billing_frequency',
                'id'
            );

            $tablesToCreate[] = $tableTnwSubscriptionProfile;
        }

        $tableName = 'tnw_subscriptions_subscription_profile_order';

        if (!$setup->tableExists($setup->getTable($tableName))) {

            $tableTnwSubscriptionProfileOrder = $setup->getConnection()->newTable($setup->getTable($tableName));

            $tableTnwSubscriptionProfileOrder->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
                'Entity ID'
            )->addColumn(
                'subscription_profile_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'subscription_profile_id'
            )->addColumn(
                'magento_order_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'magento_order_id'
            )->addIndex(
                $installer->getIdxName($tableName, ['magento_order_id']),
                ['magento_order_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'subscription_profile_id',
                    'tnw_subscriptions_subscription_profile',
                    'id'
                ),
                'subscription_profile_id',
                'tnw_subscriptions_subscription_profile',
                'id'
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'magento_order_id',
                    'sales_order',
                    'entity_id'
                ),
                'magento_order_id',
                'sales_order',
                'entity_id'
            );

            $tablesToCreate[] = $tableTnwSubscriptionProfileOrder;
        }

        $tableName = 'tnw_subscriptions_product_subscription_profile';

        if (!$setup->tableExists($setup->getTable($tableName))) {

            $tableTnwProductSubscriptionProfile = $setup->getConnection()->newTable($setup->getTable($tableName));

            $tableTnwProductSubscriptionProfile->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
                'Entity ID'
            )->addColumn(
                'subscription_profile_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'subscription_profile_id'
            )->addColumn(
                'magento_product_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'magento_product_id'
            )->addColumn(
                'price',
                Table::TYPE_DECIMAL,
                12,
                ['nullable' => false, 'precision' => 2],
                'price'
            )->addColumn(
                'initial_fee',
                Table::TYPE_DECIMAL,
                12,
                ['nullable' => false, 'precision' => 2],
                'initial_fee'
            )->addColumn(
                'qty',
                Table::TYPE_DECIMAL,
                12,
                ['nullable' => false, 'precision' => 4],
                'qty'
            )->addColumn(
                'purchase_type',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'purchase_type'
            )->addColumn(
                'trial_status',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'trial_status'
            )->addColumn(
                'trial_length',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'trial_length'
            )->addColumn(
                'trial_length_unit',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'trial_length_unit'
            )->addColumn(
                'trial_price',
                Table::TYPE_DECIMAL,
                12,
                ['nullable' => false, 'precision' => 2],
                'trial_length_unit'
            )->addColumn(
                'trial_start_date',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'trial_start_date'
            )->addColumn(
                'start_date',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'start_date'
            )->addColumn(
                'lock_product_price_status',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'lock_product_price_status'
            )->addColumn(
                'offer_flat_discount_status',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'offer_flat_discount_status'
            )->addColumn(
                'discount_amount',
                Table::TYPE_DECIMAL,
                12,
                ['nullable' => false, 'precision' => 2],
                'discount_amount'
            )->addColumn(
                'discount_type',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'unsigned' => true],
                'discount_type'
            )->addIndex(
                $installer->getIdxName($tableName, ['subscription_profile_id', 'magento_product_id']),
                ['subscription_profile_id', 'magento_product_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'subscription_profile_id',
                    'tnw_subscriptions_subscription_profile',
                    'id'
                ),
                'subscription_profile_id',
                'tnw_subscriptions_subscription_profile',
                'id'
            );

            $tablesToCreate[] = $tableTnwProductSubscriptionProfile;
        }

        foreach ($tablesToCreate as $tableToCreate) {
            $setup->getConnection()->createTable($tableToCreate);
        }

        $setup->endSetup();
    }
}
