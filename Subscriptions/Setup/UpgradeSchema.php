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
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Upgrade schema for TNW Subscriptions.
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
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
            $this->migrateSubscriptionProfileToEav($setup);
        }

        if (version_compare($context->getVersion(), "2.0.5", "<")) {
            $this->migrateProductSubscriptionProfileToEav($setup);
        }

        if (version_compare($context->getVersion(), "2.0.6", "<")) {
            $table = $setup->getTable(
                ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
            );

            $setup->getConnection()->addColumn(
                $table,
                \TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface::PRESET_QTY,
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Preset Qty',
                    'unsigned' => true,
                    'default' => null
                ]
            );
        }

        if (version_compare($context->getVersion(), "2.0.10", "<")) {
            //TODO don't add this attributes to subscription profile entity
            $setup->getConnection()->dropColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'shipping_address_id'
            );
            $setup->getConnection()->dropColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'billing_address_id'
            );
            $setup->getConnection()->dropColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'label'
            );

            //TODO don't add this attributes to subscription profile product entity
            $setup->getConnection()->dropColumn(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'trial_start_date'
            );
            $setup->getConnection()->dropColumn(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'start_date'
            );
            $setup->getConnection()->dropColumn(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'trial_length'
            );
            $setup->getConnection()->dropColumn(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'trial_length_unit'
            );

            //TODO add this attributes to main eav setup
            $setup->getConnection()->addColumn(
                SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                SubscriptionProfile::TRIAL_START_DATE,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    'comment' => 'Trial Start Date',
                ]
            );
            $setup->getConnection()->addColumn(
                SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                SubscriptionProfile::START_DATE,
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    'comment' => 'Start Date',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::TERM,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'length' => 1,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Term',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::TOTAL_BILLING_CYCLES,
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Total Billing Cycles',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::SHIPPING_METHOD,
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment' => 'Shipping Method',
                    'length' => 40
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::SHIPPING_DESCRIPTION,
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment' => 'Shipping Description',
                    'length' => 255
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::PROFILE_CURRENCY_CODE,
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment' => 'Profile Currency Code',
                    'length' => 255
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::TRIAL_LENGTH,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'length' => 5,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Trial Length',
                ]
            );
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::TRIAL_LENGTH_UNIT,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'length' => 1,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Trial Length Unit',
                ]
            );

            //TODO on install add new foreign key
            $tableName = 'tnw_subscriptions_subscription_profile_order';
            $setup->getConnection()->dropForeignKey(
                $tableName,
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'subscription_profile_id',
                    'tnw_subscriptions_subscription_profile',
                    'id'
                )
            );
            $setup->getConnection()->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'subscription_profile_id',
                    'tnw_subscriptions_subscription_profile',
                    'id'
                ),
                $tableName,
                'subscription_profile_id',
                'tnw_subscriptions_subscription_profile_entity',
                'entity_id',
                'NO ACTION'
            );

            $table = $setup->getConnection()->newTable(
                $setup->getTable('tnw_subscriptions_subscription_profile_address')
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )->addColumn(
                'profile_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Profile Id'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_UPDATE],
                'Updated At'
            )->addColumn(
                'customer_address_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Customer Address Id'
            )->addColumn(
                'address_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                10,
                [],
                'Address Type'
            )->addColumn(
                'prefix',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                40,
                [],
                'Prefix'
            )->addColumn(
                'firstname',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                [],
                'Firstname'
            )->addColumn(
                'middlename',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                [],
                'Middlename'
            )->addColumn(
                'lastname',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                [],
                'Lastname'
            )->addColumn(
                'suffix',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                40,
                [],
                'Suffix'
            )->addColumn(
                'company',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Company'
            )->addColumn(
                'street',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                40,
                [],
                'Street'
            )->addColumn(
                'city',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                40,
                [],
                'City'
            )->addColumn(
                'region',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                40,
                [],
                'Region'
            )->addColumn(
                'region_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true],
                'Region Id'
            )->addColumn(
                'postcode',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                [],
                'Postcode'
            )->addColumn(
                'country_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                30,
                [],
                'Country Id'
            )->addColumn(
                'telephone',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                [],
                'Phone Number'
            )->addColumn(
                'fax',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                20,
                [],
                'Fax'
            )->addIndex(
                $setup->getIdxName('tnw_subscriptions_subscription_profile_address', ['profile_id']),
                ['profile_id']
            )->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_subscription_profile_address',
                    'profile_id',
                    'tnw_subscriptions_subscription_profile_entity',
                    'entity_id'
                ),
                'profile_id',
                $setup->getTable('tnw_subscriptions_subscription_profile_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Subscription Profile Address'
            );
            $setup->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), "2.0.11", "<")) {
            //TODO add this attributes to main eav setup
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::IS_VIRTUAL,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'comment' => 'Is virtual',
                    'length' => 1,
                    'default' => '0'
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::SHIPPING_METHOD,
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Shipping Method',
                    'length' => 40,
                    'default' => null
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::SHIPPING_DESCRIPTION,
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Shipping Description',
                    'length' => 255,
                    'default' => null
                ]
            );
        }

        if (version_compare($context->getVersion(), "2.0.12", "<")) {
            //TODO add this attribute to main eav setup
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::TOKEN_HASH,
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Token Hash',
                    'length' => 128,
                    'default' => null
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::PAYMENT_ADDITIONAL_INFO,
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Payment Additional Info',
                    'default' => null
                ]
            );

            //TODO on install add new foreign key
            $tableName = 'tnw_subscriptions_subscription_profile_order';
            $setup->getConnection()->dropForeignKey(
                $tableName,
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'subscription_profile_id',
                    'tnw_subscriptions_subscription_profile',
                    'id'
                )
            );
            $setup->getConnection()->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'subscription_profile_id',
                    'tnw_subscriptions_subscription_profile_entity',
                    'id'
                ),
                $tableName,
                'subscription_profile_id',
                'tnw_subscriptions_subscription_profile_entity',
                'entity_id',
                'CASCADE'
            );

            //TODO on install add new foreign key
            $tableName = 'tnw_subscriptions_subscription_profile_order';
            $setup->getConnection()->dropForeignKey(
                $tableName,
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'magento_order_id',
                    'sales_order',
                    'entity_id'
                )
            );
            $setup->getConnection()->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'magento_order_id',
                    'sales_order',
                    'entity_id'
                ),
                $tableName,
                'magento_order_id',
                'sales_order',
                'entity_id',
                'CASCADE'
            );

            //TODO on install add new foreign key
            $setup->getConnection()->dropForeignKey(
                ProductSubscriptionProfile::ENTITY_TABLE,
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'subscription_profile_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                )
            );
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'subscription_profile_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                ProductSubscriptionProfile::ENTITY_TABLE,
                'subscription_profile_id',
                SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                'entity_id',
                'CASCADE'
            );

            //TODO on install add new foreign key
            $setup->getConnection()->dropForeignKey(
                ProductSubscriptionProfile::ENTITY_TABLE,
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'magento_product_id',
                    'catalog_product_entity',
                    'entity_id'
                )
            );
            $setup->getConnection()->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'magento_product_id',
                    'catalog_product_entity',
                    'entity_id'
                ),
                ProductSubscriptionProfile::ENTITY_TABLE,
                'magento_product_id',
                'catalog_product_entity',
                'entity_id',
                'CASCADE'
            );
        }

        if (version_compare($context->getVersion(), "2.0.13", "<")) {
            //TODO add this attribute to main eav setup
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::NEED_GENERATE_QUOTES,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'comment' => 'Need generate quotes',
                    'length' => 1,
                    'default' => '0'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfileOrder::MAIN_TABLE),
                SubscriptionProfileOrder::MAGENTO_QUOTE_ID,
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Magento quote id',
                    'unsigned' => true,
                    'default' => null
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfileOrder::MAIN_TABLE),
                SubscriptionProfileOrder::SCHEDULED_AT,
                [
                    'type' => Table::TYPE_DATETIME,
                    'comment' => 'Scheduled at',
                ]
            );

            $setup->getConnection()->dropForeignKey(
                SubscriptionProfileOrder::MAIN_TABLE,
                $setup->getFkName(
                    SubscriptionProfileOrder::MAIN_TABLE,
                    'magento_order_id',
                    'sales_order',
                    'entity_id'
                )
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable(SubscriptionProfileOrder::MAIN_TABLE),
                SubscriptionProfileOrder::MAGENTO_ORDER_ID,
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Magento order id',
                    'unsigned' => true,
                    'default' => null
                ]
            );
            //TODO update main eav setup for this attributes
            $setup->getConnection()->modifyColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::CREATED_AT,
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::UPDATED_AT,
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT_UPDATE
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                ProductSubscriptionProfile::CREATED_AT,
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT
                ]
            );

            $setup->getConnection()->modifyColumn(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                ProductSubscriptionProfile::UPDATED_AT,
                [
                    'type' => Table::TYPE_TIMESTAMP,
                    'nullable' => false,
                    'default' => Table::TIMESTAMP_INIT_UPDATE
                ]
            );

            if (!$setup->tableExists(Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE)) {
                $table = $setup->getConnection()
                    ->newTable(Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE)
                    ->addColumn(
                        Queue::ID,
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'primary' => true,
                            'nullable' => false
                        ]
                    )->addColumn(
                        Queue::PROFILE_ORDER_ID,
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'unsigned' => true,
                            'nullable' => false
                        ]
                    )->addColumn(
                        Queue::STATUS,
                        Table::TYPE_TEXT,
                        255,
                        [
                            'nullable' => false,
                            'default' => 'new'
                        ]
                    )->addColumn(
                        Queue::ATTEMPT_COUNT,
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'unsigned' => true,
                            'nullable' => false,
                            'default' => 0
                        ]
                    )->addColumn(
                        Queue::MESSAGE,
                        Table::TYPE_TEXT,
                        1024,
                        [
                            'unsigned' => true,
                            'nullable' => true
                        ],
                        'Error when sync'
                    )->addColumn(
                        Queue::CREATED_AT,
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                        'Creation Time'
                    )
                    ->addColumn(
                        Queue::UPDATED_AT,
                        Table::TYPE_TIMESTAMP,
                        null,
                        ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                        'Update Time'
                    )->addIndex(
                        $setup->getIdxName(
                            Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE,
                            [Queue::CREATED_AT]
                        ),
                        Queue::CREATED_AT
                    )->addIndex(
                        $setup->getIdxName(
                            Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE,
                            [Queue::UPDATED_AT]
                        ),
                        Queue::UPDATED_AT
                    )->addIndex(
                        $setup->getIdxName(
                            Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE,
                            [Queue::STATUS]
                        ),
                        Queue::STATUS
                    )->addIndex(
                        $setup->getIdxName(
                            Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE,
                            [Queue::ATTEMPT_COUNT]
                        ),
                        Queue::ATTEMPT_COUNT
                    )->addIndex(
                        $setup->getIdxName(
                            Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE,
                            [Queue::PROFILE_ORDER_ID],
                            AdapterInterface::INDEX_TYPE_UNIQUE
                        ),
                        Queue::PROFILE_ORDER_ID,
                        ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                    )->addForeignKey(
                        $setup->getConnection()->getForeignKeyName(
                            Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE,
                            Queue::PROFILE_ORDER_ID,
                            SubscriptionProfileOrder::MAIN_TABLE,
                            SubscriptionProfileOrder::ID
                        ),
                        Queue::PROFILE_ORDER_ID,
                        SubscriptionProfileOrder::MAIN_TABLE,
                        SubscriptionProfileOrder::ID,
                        AdapterInterface::FK_ACTION_CASCADE
                    );

                $setup->getConnection()->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), "2.0.14", "<")) {
            $this->addSubscriptionProfileMessageHistoryTable($setup);
        }

        if (version_compare($context->getVersion(), "2.0.17", "<")) {
            $this->addQuoteAndOrderItemExtentionAttributeTables($setup);
        }

        if (version_compare($context->getVersion(), "2.0.18", "<")) {
            //TODO add this attributes to main eav setup
            $setup->getConnection()->addColumn(
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::NEED_RECOLLECT,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'comment' => 'Need Recollect',
                    'length' => 1,
                    'default' => '0'
                ]
            );

            //TODO add this attributes to main eav setup
            $setup->getConnection()->addColumn(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                ProductSubscriptionProfile::NEED_RECOLLECT,
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'comment' => 'Need Recollect',
                    'length' => 1,
                    'default' => '0'
                ]
            );
        }

        $setup->endSetup();
    }

    /**
     * Migrate Product Subscription Profile to Eav structure.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function migrateProductSubscriptionProfileToEav(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'tnw_product_subscription_profile_entity'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )
            ->addColumn(
                'subscription_profile_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false],
                'Subscription Profile ID'
            )
            ->addColumn(
                'magento_product_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false],
                'Product ID'
            )
            ->addColumn(
                'price',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Price'
            )
            ->addColumn(
                'initial_fee',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Initial Fee'
            )
            ->addColumn(
                'qty',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Qty'
            )
            ->addColumn(
                'purchase_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false],
                'Purchase Type'
            )
            ->addColumn(
                'trial_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Trial Status'
            )
            ->addColumn(
                'trial_length',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                5,
                ['unsigned' => true, 'nullable' => false],
                'Trial Length'
            )
            ->addColumn(
                'trial_length_unit',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false],
                'Trial Length Unit'
            )
            ->addColumn(
                'trial_price',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Trial Price'
            )
            ->addColumn(
                'trial_start_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false],
                'Trial Start Date'
            )
            ->addColumn(
                'start_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false],
                'Start Date'
            )
            ->addColumn(
                'lock_product_price_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Lock product price'
            )
            ->addColumn(
                'offer_flat_discount_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Offer flat discount'
            )
            ->addColumn(
                'discount_amount',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Discount amount'
            )
            ->addColumn(
                'discount_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false],
                'Discount type'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Creation time'
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                'Update time'
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE, ['subscription_profile_id']),
                ['subscription_profile_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE, ['magento_product_id']),
                ['magento_product_id']
            )
            ->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    ['subscription_profile_id', 'magento_product_id']
                ),
                ['subscription_profile_id', 'magento_product_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'subscription_profile_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                'subscription_profile_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'entity_id'
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'magento_product_id',
                    'catalog_product_entity',
                    'entity_id'
                ),
                'magento_product_id',
                $setup->getTable('catalog_product_entity'),
                'entity_id'
            )
            ->setComment('Product Subscription Profile Table');
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'tnw_product_subscription_profile_entity_datetime'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime'))
            ->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value ID'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Value'
            )
            ->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_datetime',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime', ['entity_id']),
                ['entity_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime', ['attribute_id']),
                ['attribute_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_datetime',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_datetime',
                    'entity_id',
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime', 'store_id', 'store',
                    'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Product Subscription Profile Datetime Attribute Backend Table');
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'tnw_product_subscription_profile_entity_decimal'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal'))
            ->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value ID'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Value'
            )
            ->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_decimal',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal', ['entity_id']),
                ['entity_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal', ['attribute_id']),
                ['attribute_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_decimal',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_decimal',
                    'entity_id',
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal', 'store_id', 'store',
                    'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Product Subscription Profile Decimal Attribute Backend Table');
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'tnw_product_subscription_profile_entity_int'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE . '_int'))
            ->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value ID'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Value'
            )
            ->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_int',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_int', ['entity_id']),
                ['entity_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_int', ['attribute_id']),
                ['attribute_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_int', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_int',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_int',
                    'entity_id',
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable('catalog_category_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(ProductSubscriptionProfile::ENTITY_TABLE . '_int', 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Product Subscription Profile Integer Attribute Backend Table');
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'tnw_product_subscription_profile_entity_text'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE . '_text'))
            ->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value ID'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Value'
            )
            ->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_text',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_text', ['entity_id']),
                ['entity_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_text', ['attribute_id']),
                ['attribute_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_text', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_text',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_text',
                    'entity_id',
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(ProductSubscriptionProfile::ENTITY_TABLE . '_text', 'store_id', 'store', 'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Product Subscription Profile Text Attribute Backend Table');
        $setup->getConnection()->createTable($table);

        /**
         * Create table 'tnw_product_subscription_profile_entity_varchar'
         */
        $table = $setup->getConnection()
            ->newTable($setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar'))
            ->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Value ID'
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )
            ->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Value'
            )
            ->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_varchar',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar', ['entity_id']),
                ['entity_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar', ['attribute_id']),
                ['attribute_id']
            )
            ->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar', ['store_id']),
                ['store_id']
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_varchar',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_varchar',
                    'entity_id',
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar', 'store_id', 'store',
                    'store_id'),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Product Subscription Profile Varchar Attribute Backend Table');
        $setup->getConnection()->createTable($table);
    }

    /**
     * Migrate Subscription Profile to Eav structure.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function migrateSubscriptionProfileToEav(SchemaSetupInterface $setup)
    {
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

    /**
     * Create table 'tnw_subscriptions_subscription_profile_message_history'.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addSubscriptionProfileMessageHistoryTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('tnw_subscriptions_subscription_profile_message_history')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Entity Id'
        )->addColumn(
            'parent_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Parent Id'
        )->addColumn(
            'is_visible_on_front',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Is Visible On Front'
        )->addColumn(
            'message',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '64k',
            [],
            'Message'
        )->addColumn(
            'is_comment',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Is it comment or message.'
        )->addColumn(
            'created_at',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
            'Created At'
        )->addColumn(
            'user_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'User id whose message it is'
        )->addIndex(
            $setup->getIdxName('tnw_subscriptions_subscription_profile_message_history', ['parent_id']),
            ['parent_id']
        )->addForeignKey(
            $setup->getFkName(
                'tnw_subscriptions_subscription_profile_message_history',
                'parent_id',
                'tnw_subscriptions_subscription_profile_entity',
                'entity_id'
            ),
            'parent_id',
            $setup->getTable('tnw_subscriptions_subscription_profile_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Subscription profile message history table.'
        );

        $setup->getConnection()->createTable($table);
    }

    /**
     * Create table 'tnw_subscriptions_subscription_profile_message_history'.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addQuoteAndOrderItemExtentionAttributeTables(SchemaSetupInterface $setup)
    {
        if (!$setup->tableExists(SalesExtensionAttributesInterface::QUOTE_ITEM_EXTENSION_TABLE)) {
            $table = $setup->getConnection()->newTable(
                SalesExtensionAttributesInterface::QUOTE_ITEM_EXTENSION_TABLE
            )->addColumn(
                SalesExtensionAttributesInterface::MAGENTO_ITEM_ID, Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Magento quote item ID'
            )->addColumn(
                SalesExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE,
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false,
                    'default' => '0.0000'
                ],
                'Initial fee'
            )->addColumn(
                SalesExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE,
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false,
                    'default' => '0.0000'
                ],
                'Base initial fee'
            );

            $setup->getConnection()->createTable($table);
        }

        if (!$setup->tableExists(SalesExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE)) {
            $table = $setup->getConnection()->newTable(
                SalesExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE
            )->addColumn(
                SalesExtensionAttributesInterface::MAGENTO_ITEM_ID, Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Magento order item ID'
            )->addColumn(
                SalesExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE,
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false,
                    'default' => '0.0000'
                ],
                'Initial fee'
            )->addColumn(
                SalesExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE,
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false,
                    'default' => '0.0000'
                ],
                'Base initial fee'
            );

            $setup->getConnection()->createTable($table);
        }
    }
}
