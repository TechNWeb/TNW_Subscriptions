<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Upgrade data for TNW Subscriptions.
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        if (version_compare($context->getVersion(), "2.0.1", "<")) {
            $tableName = 'tnw_subscriptions_product_billing_frequency';
            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'sort_order',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'sort_order',
                ]
            );

        }

        if (version_compare($context->getVersion(), "2.0.2", "<")) {
            $tableName = 'tnw_subscriptions_subscription_profile';
            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'engine_code',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment' => 'Engine code',
                    'length' => 255
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'shipping_address_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'shipping address id'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'billing_address_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'shipping address id'
                ]
            );
        }

        if (version_compare($context->getVersion(), "2.0.3", "<")) {
            /**
             * Create table 'tnw_subscriptions_subscription_profile_entity'.
             */
            $table = $setup->getConnection()->newTable(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY)
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Customer Id'
            )->addColumn(
                'billing_frequency_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Billing Frequency Id'
            )->addColumn(
                'label',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Label'
            )->addColumn(
                'unit',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Customer Id'
            )->addColumn(
                'website_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Website Id'
            )->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false],
                'Status'
            )->addColumn(
                'frequency',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Frequency'
            )->addColumn(
                'engine_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Engine Code'
            )->addColumn(
                'shipping_address_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Shipping Address Id'
            )->addColumn(
                'billing_address_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Billing Address Id'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Shipping Address Id'
            )->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Billing Address Id'
            );
            $setup->getConnection()->createTable($table);

            /**
             * Create table 'tnw_subscriptions_subscription_profile_entity_datetime'.
             */
            $table = $setup->getConnection()->newTable(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_datetime')
            )->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value Id'
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Id'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity Id'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['nullable' => true, 'default' => null],
                'Value'
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_datetime',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_datetime',
                    'entity_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Subscription Profile Entity Datetime'
            );
            $setup->getConnection()->createTable($table);

            /**
             * Create table 'tnw_subscriptions_subscription_profile_entity_decimal'.
             */
            $table = $setup->getConnection()->newTable(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_decimal')
            )->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value Id'
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Id'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity Id'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'default' => '0.0000'],
                'Value'
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_decimal',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_decimal',
                    'entity_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Subscription Profile Entity Decimal'
            );
            $setup->getConnection()->createTable($table);

            /**
             * Create table 'tnw_subscriptions_subscription_profile_entity_int'.
             */
            $table = $setup->getConnection()->newTable(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_int')
            )->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value Id'
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Id'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity Id'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => '0'],
                'Value'
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_int',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_int',
                    'entity_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Subscription Profile Entity Int'
            );
            $setup->getConnection()->createTable($table);

            /**
             * Create table 'tnw_subscriptions_subscription_profile_entity_text'.
             */
            $table = $setup->getConnection()->newTable(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_text')
            )->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value Id'
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Id'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity Id'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                ['nullable' => false],
                'Value'
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_text',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_text',
                    'entity_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Subscription Profile Entity Text'
            );
            $setup->getConnection()->createTable($table);

            /**
             * Create table 'tnw_subscriptions_subscription_profile_entity_varchar'.
             */
            $table = $setup->getConnection()->newTable(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_varchar')
            )->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value Id'
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute Id'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity Id'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Value'
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_varchar',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_varchar',
                    'entity_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Customer Address Entity Varchar'
            );
            $setup->getConnection()->createTable($table);

            //drop old table if exist.
            $setup->getConnection()->dropTable($setup->getTable('tnw_subscriptions_subscription_profile'));
        }

        $setup->endSetup();
    }
}
