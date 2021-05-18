<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use TNW\Subscriptions\Api\Data\BillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\OrderItemExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Api\Data\QuoteItemExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\CustomerQuote;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Address;
use TNW\Subscriptions\Model\SubscriptionProfileStatusHistory;

/**
 * DB schema installs
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @inheritdoc
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $this->addBillingFrequencyTable($setup);
        $this->addProductBillingFrequencyTable($setup);
        $this->addSubscriptionProfileOrderTable($setup);
        $this->addSubscriptionProfileMessageHistoryTable($setup);
        $this->addQuoteAndOrderItemExtentionAttributeTables($setup);
        $this->addCustomerQuoteTable($setup);
        $this->addSubscriptionProfileStatusHistoryTable($setup);
        $this->addSubscriptionProfileAddressTable($setup);
        $this->addSubscriptionsQueueTable($setup);
        $this->createProductSubscriptionProfileEav($setup);
        $this->createSubscriptionProfileEav($setup);
        $this->addSubscriptionProfileIdColumnToSalesOrderGrid($setup);

        $setup->endSetup();
    }

    /**
     * Create table 'tnw_subscriptions_billing_frequency'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addBillingFrequencyTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE);

        if (!$setup->tableExists($tableName)) {

            $tableTnwBillingFrequency = $setup->getConnection()->newTable($tableName);

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
                $setup->getTable('store_website'),
                'website_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
            $setup->getConnection()->createTable($tableTnwBillingFrequency);
        }
    }

    /**
     * Create table 'tnw_subscriptions_product_billing_frequency'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addProductBillingFrequencyTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(
            ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
        );

        if (!$setup->tableExists($tableName)) {

            $tableTnwProductBillingFrequency = $setup->getConnection()->newTable($tableName);

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
                '12,2',
                ['nullable' => false,],
                'price'
            )->addColumn(
                'initial_fee',
                Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false,],
                'initial_fee'
            )->addColumn(
                'sort_order',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false,],
                'Sort order'
            )->addColumn(
                ProductBillingFrequencyInterface::PRESET_QTY,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null,],
                'Preset Qty'
            )->addIndex(
                $setup->getIdxName(
                    $tableName,
                    ['billing_frequency_id', 'magento_product_id']
                ),
                ['billing_frequency_id', 'magento_product_id'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    'billing_frequency_id',
                    BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE,
                    'id'
                ),
                'billing_frequency_id',
                $setup->getTable(BillingFrequencyInterface::SUBSCRIPTIONS_BILLING_FREQUENCY_TABLE),
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
                $setup->getTable('catalog_product_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            );
            $setup->getConnection()->createTable($tableTnwProductBillingFrequency);
        }
    }

    /**
     * Create table 'tnw_subscriptions_product_billing_frequency'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addSubscriptionProfileAddressTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(Address::SUBSCRIPTION_PROFILE_ADDRESS_TABLE);

        if (!$setup->tableExists($tableName)) {
            $table = $setup->getConnection()->newTable($tableName)
                ->addColumn(
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
                    $setup->getIdxName($tableName, ['profile_id']),
                    ['profile_id']
                )->addForeignKey(
                    $setup->getFkName(
                        $tableName,
                        'profile_id',
                        SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                        SubscriptionProfile::ID
                    ),
                    'profile_id',
                    $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                    SubscriptionProfile::ID,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )->setComment(
                    'Subscription Profile Address'
                );
            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * Create table 'tnw_subscriptions_subscription_profile_order'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addSubscriptionProfileOrderTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE);

        if (!$setup->tableExists($tableName)) {
            $tableTnwSubscriptionProfileOrder = $setup->getConnection()->newTable($tableName)
                ->addColumn(
                    SubscriptionProfileOrderInterface::ID,
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
                    'Entity ID'
                )->addColumn(
                    SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'unsigned' => true],
                    'Subscription profile id'
                )->addColumn(
                    SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => true, 'unsigned' => true, 'default' => null,],
                    'Magento order id'
                )->addColumn(
                    SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => true, 'unsigned' => true, 'default' => null,],
                    'Magento quote id'
                )->addColumn(
                    SubscriptionProfileOrderInterface::SCHEDULED_AT,
                    Table::TYPE_DATETIME,
                    null,
                    [],
                    'Scheduled at'
                )->addIndex(
                    $setup->getIdxName($tableName, ['magento_order_id']),
                    ['magento_order_id'],
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                )->addForeignKey(
                    $setup->getConnection()->getForeignKeyName(
                        $tableName,
                        'subscription_profile_id',
                        SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                        SubscriptionProfile::ID
                    ),
                    'subscription_profile_id',
                    $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                    SubscriptionProfile::ID,
                    \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                );

            $setup->getConnection()->createTable($tableTnwSubscriptionProfileOrder);
        }
    }

    /**
     * Create table 'tnw_subscriptions_subscription_profile_message_history'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addSubscriptionProfileMessageHistoryTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('tnw_subscriptions_subscription_profile_message_history');
        if (!$setup->tableExists($tableName)) {
            $table = $setup->getConnection()->newTable($tableName)->addColumn(
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
            )->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => true],
                'Customer id whose message it is'
            )->addIndex(
                $setup->getIdxName($tableName, ['parent_id']),
                ['parent_id']
            )->addForeignKey(
                $setup->getFkName(
                    $tableName,
                    'parent_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    SubscriptionProfile::ID
                ),
                'parent_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::ID,
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment(
                'Subscription profile message history table.'
            );

            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * Create 'tnw_subscriptions_quote_item_extension_entity', 'tnw_subscriptions_quote_item_extension_entity' tables.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addQuoteAndOrderItemExtentionAttributeTables(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(QuoteItemExtensionAttributesInterface::QUOTE_ITEM_EXTENSION_TABLE);
        if (!$setup->tableExists($tableName)) {
            $table = $setup->getConnection()->newTable($tableName)
                ->addColumn(
                    SalesExtensionAttributesInterface::MAGENTO_ITEM_ID,
                    Table::TYPE_INTEGER,
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

        $tableName = $setup->getTable(OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE);

        if (!$setup->tableExists($tableName)) {
            $table = $setup->getConnection()->newTable($tableName)->addColumn(
                SalesExtensionAttributesInterface::MAGENTO_ITEM_ID,
                Table::TYPE_INTEGER,
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

    /**
     * Create table 'tnw_subscriptions_customer_quote'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addCustomerQuoteTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(CustomerQuote::CUSTOMER_QUOTE_TABLE);
        if (!$setup->tableExists($tableName)) {
            $table = $setup->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    CustomerQuote::ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'primary' => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    CustomerQuote::CUSTOMER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    CustomerQuote::QUOTE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ]
                )->addForeignKey(
                    $setup->getConnection()->getForeignKeyName(
                        $tableName,
                        CustomerQuote::QUOTE_ID,
                        'quote',
                        'entity_id'
                    ),
                    CustomerQuote::QUOTE_ID,
                    $setup->getTable('quote'),
                    'entity_id',
                    AdapterInterface::FK_ACTION_CASCADE
                )->addForeignKey(
                    $setup->getConnection()->getForeignKeyName(
                        $tableName,
                        CustomerQuote::CUSTOMER_ID,
                        'customer_entity',
                        'entity_id'
                    ),
                    CustomerQuote::CUSTOMER_ID,
                    $setup->getTable('customer_entity'),
                    'entity_id',
                    AdapterInterface::FK_ACTION_CASCADE
                );

            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * Create table 'tnw_subscriptions_subscription_profile_status_history'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addSubscriptionProfileStatusHistoryTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(SubscriptionProfileStatusHistory::TABLE);

        if (!$setup->tableExists($tableName)) {
            $tableTnwSubscriptionProfileStatusHistory = $setup->getConnection()->newTable($tableName)
                ->addColumn(
                    SubscriptionProfileStatusHistory::ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'primary' => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    SubscriptionProfileStatusHistory::SUBSCRIPTION_PROFILE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ]
                )->addColumn(
                    SubscriptionProfileStatusHistory::STATUS_OLD,
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => true],
                    'Old status'
                )
                ->addColumn(
                    SubscriptionProfileStatusHistory::STATUS_NEW,
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false],
                    'New status'
                )
                ->addColumn(
                    SubscriptionProfileStatusHistory::USER_ID,
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null
                    ],
                    'User id who changed status'
                )
                ->addColumn(
                    SubscriptionProfileStatusHistory::CUSTOMER_ID,
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                        'default' => null
                    ],
                    'Customer id who changed status'
                )
                ->addColumn(
                    SubscriptionProfileStatusHistory::CHANGED_AT,
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false],
                    'Changed at'
                )
                ->addColumn(
                    SubscriptionProfileStatusHistory::CHANGED_AT_MICRO,
                    Table::TYPE_BIGINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'Changed at microseconds'
                )
                ->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [SubscriptionProfileStatusHistory::SUBSCRIPTION_PROFILE_ID],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    SubscriptionProfileStatusHistory::SUBSCRIPTION_PROFILE_ID
                )
                ->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [SubscriptionProfileStatusHistory::STATUS_OLD]
                    ),
                    SubscriptionProfileStatusHistory::STATUS_OLD
                )
                ->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [SubscriptionProfileStatusHistory::STATUS_NEW]
                    ),
                    SubscriptionProfileStatusHistory::STATUS_NEW
                )
                ->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [SubscriptionProfileStatusHistory::USER_ID]
                    ),
                    SubscriptionProfileStatusHistory::USER_ID
                )
                ->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [SubscriptionProfileStatusHistory::CUSTOMER_ID]
                    ),
                    SubscriptionProfileStatusHistory::CUSTOMER_ID
                )
                ->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [SubscriptionProfileStatusHistory::CHANGED_AT]
                    ),
                    SubscriptionProfileStatusHistory::CHANGED_AT
                )
                ->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [SubscriptionProfileStatusHistory::CHANGED_AT_MICRO]
                    ),
                    SubscriptionProfileStatusHistory::CHANGED_AT_MICRO
                )
                ->addForeignKey(
                    $setup->getConnection()->getForeignKeyName(
                        $tableName,
                        SubscriptionProfileStatusHistory::SUBSCRIPTION_PROFILE_ID,
                        SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                        SubscriptionProfile::ID
                    ),
                    SubscriptionProfileStatusHistory::SUBSCRIPTION_PROFILE_ID,
                    $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                    SubscriptionProfile::ID,
                    AdapterInterface::FK_ACTION_CASCADE
                );

            $setup->getConnection()->createTable($tableTnwSubscriptionProfileStatusHistory);
        }
    }

    /**
     * Create table 'tnw_subscriptions_subscription_profile_queue'.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addSubscriptionsQueueTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE);

        if (!$setup->tableExists($tableName)) {
            $table = $setup->getConnection()->newTable($tableName)
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
                )->addColumn(
                    Queue::UPDATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Update Time'
                )->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [Queue::CREATED_AT]
                    ),
                    Queue::CREATED_AT
                )->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [Queue::UPDATED_AT]
                    ),
                    Queue::UPDATED_AT
                )->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [Queue::STATUS]
                    ),
                    Queue::STATUS
                )->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [Queue::ATTEMPT_COUNT]
                    ),
                    Queue::ATTEMPT_COUNT
                )->addIndex(
                    $setup->getIdxName(
                        $tableName,
                        [Queue::PROFILE_ORDER_ID],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    Queue::PROFILE_ORDER_ID,
                    ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                )->addForeignKey(
                    $setup->getConnection()->getForeignKeyName(
                        $tableName,
                        Queue::PROFILE_ORDER_ID,
                        SubscriptionProfileOrderInterface::MAIN_TABLE,
                        SubscriptionProfileOrderInterface::ID
                    ),
                    Queue::PROFILE_ORDER_ID,
                    $setup->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE),
                    SubscriptionProfileOrderInterface::ID,
                    AdapterInterface::FK_ACTION_CASCADE
                );

            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * Create Product Subscription Profile Eav structure.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function createProductSubscriptionProfileEav(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'tnw_product_subscription_profile_entity'
         */
        $entityTable = $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE);
        $table = $setup->getConnection()->newTable($entityTable)
            ->addColumn(
                ProductSubscriptionProfile::ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity ID'
            )->addColumn(
                ProductSubscriptionProfile::PARENT_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true,],
                'Parent id'
            )->addColumn(
                ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false],
                'Subscription Profile ID'
            )->addColumn(
                ProductSubscriptionProfile::MAGENTO_PRODUCT_ID,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false],
                'Product ID'
            )->addColumn(
                ProductSubscriptionProfile::NAME,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true,],
                'Product name'
            )->addColumn(
                ProductSubscriptionProfile::SKU,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                64,
                ['nullable' => true,],
                'Product sku'
            )->addColumn(
                ProductSubscriptionProfile::TNW_SUBSCR_UNLOCK_PRESET_QTY,
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11,
                ['nullable' => true,],
                'Preset qty'
            )->addColumn(
                ProductSubscriptionProfile::PRICE,
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Price'
            )->addColumn(
                ProductSubscriptionProfile::INITIAL_FEE,
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Initial Fee'
            )->addColumn(
                ProductSubscriptionProfile::QTY,
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Qty'
            )->addColumn(
                ProductSubscriptionProfile::PURCHASE_TYPE,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false],
                'Purchase Type'
            )->addColumn(
                ProductSubscriptionProfile::TRIAL_STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Trial Status'
            )->addColumn(
                ProductSubscriptionProfile::TRIAL_PRICE,
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Trial Price'
            )->addColumn(
                ProductSubscriptionProfile::LOCK_PRODUCT_PRICE_STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Lock product price'
            )->addColumn(
                ProductSubscriptionProfile::OFFER_FLAT_DISCOUNT_STATUS,
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Offer flat discount'
            )->addColumn(
                ProductSubscriptionProfile::DISCOUNT_AMOUNT,
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0', 'precision' => 12, 'scale' => 4],
                'Discount amount'
            )->addColumn(
                ProductSubscriptionProfile::DISCOUNT_TYPE,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false],
                'Discount type'
            )->addColumn(
                ProductSubscriptionProfile::NEED_RECOLLECT,
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['unsigned' => true, 'nullable' => false, 'default' => '0',],
                'Need Recollect'
            )->addColumn(
                ProductSubscriptionProfile::CUSTOM_OPTIONS,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true,],
                'Custom Options'
            )->addColumn(
                ProductSubscriptionProfile::CREATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Creation time'
            )->addColumn(
                ProductSubscriptionProfile::UPDATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                'Update time'
            )->addIndex(
                $setup->getIdxName($entityTable, [ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID]),
                [ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID]
            )->addIndex(
                $setup->getIdxName($entityTable, [ProductSubscriptionProfile::MAGENTO_PRODUCT_ID]),
                [ProductSubscriptionProfile::MAGENTO_PRODUCT_ID]
            )->addIndex(
                $setup->getIdxName(
                    $entityTable,
                    [
                        ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID,
                        ProductSubscriptionProfile::MAGENTO_PRODUCT_ID
                    ]
                ),
                [ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID, ProductSubscriptionProfile::MAGENTO_PRODUCT_ID],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'subscription_profile_id',
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    'entity_id'
                ),
                'subscription_profile_id',
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE,
                    'magento_product_id',
                    'catalog_product_entity',
                    'entity_id'
                ),
                'magento_product_id',
                $setup->getTable('catalog_product_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Product Subscription Profile Table');
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
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Value'
            )->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_datetime',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime', ['entity_id']),
                ['entity_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime', ['attribute_id']),
                ['attribute_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_datetime', ['store_id']),
                ['store_id']
            )->addForeignKey(
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
            )->addForeignKey(
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
            )->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_datetime',
                    'store_id',
                    'store',
                    'store_id'
                ),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Product Subscription Profile Datetime Attribute Backend Table');
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
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Value'
            )->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_decimal',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal', ['entity_id']),
                ['entity_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal', ['attribute_id']),
                ['attribute_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_decimal', ['store_id']),
                ['store_id']
            )->addForeignKey(
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
            )->addForeignKey(
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
            )->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_decimal',
                    'store_id',
                    'store',
                    'store_id'
                ),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Product Subscription Profile Decimal Attribute Backend Table');
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
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [],
                'Value'
            )->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_int',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_int', ['entity_id']),
                ['entity_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_int', ['attribute_id']),
                ['attribute_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_int', ['store_id']),
                ['store_id']
            )->addForeignKey(
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
            )->addForeignKey(
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
            )->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_int',
                    'store_id',
                    'store',
                    'store_id'
                ),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Product Subscription Profile Integer Attribute Backend Table');
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
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Value'
            )->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_text',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_text', ['entity_id']),
                ['entity_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_text', ['attribute_id']),
                ['attribute_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_text', ['store_id']),
                ['store_id']
            )->addForeignKey(
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
            )->addForeignKey(
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
            )->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_text',
                    'store_id',
                    'store',
                    'store_id'
                ),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Product Subscription Profile Text Attribute Backend Table');
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
            )->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Attribute ID'
            )->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Store ID'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Entity ID'
            )->addColumn(
                'value',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                [],
                'Value'
            )->addIndex(
                $setup->getIdxName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_varchar',
                    ['entity_id', 'attribute_id', 'store_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id', 'attribute_id', 'store_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar', ['entity_id']),
                ['entity_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar', ['attribute_id']),
                ['attribute_id']
            )->addIndex(
                $setup->getIdxName(ProductSubscriptionProfile::ENTITY_TABLE . '_varchar', ['store_id']),
                ['store_id']
            )->addForeignKey(
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
            )->addForeignKey(
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
            )->addForeignKey(
                $setup->getFkName(
                    ProductSubscriptionProfile::ENTITY_TABLE . '_varchar',
                    'store_id',
                    'store',
                    'store_id'
                ),
                'store_id',
                $setup->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )->setComment('Product Subscription Profile Varchar Attribute Backend Table');
        $setup->getConnection()->createTable($table);
    }

    /**
     * Create Subscription Profile Eav structure.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function createSubscriptionProfileEav(SchemaSetupInterface $setup)
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
            SubscriptionProfile::TRIAL_START_DATE,
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Trial Start Date'
        )->addColumn(
            SubscriptionProfile::START_DATE,
            \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
            null,
            [],
            'Start Date'
        )->addColumn(
            SubscriptionProfile::TERM,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Term'
        )->addColumn(
            SubscriptionProfile::TOTAL_BILLING_CYCLES,
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Total Billing Cycles'
        )->addColumn(
            SubscriptionProfile::SHIPPING_METHOD,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            40,
            ['nullable' => true, 'default' => null],
            'Shipping Method'
        )->addColumn(
            SubscriptionProfile::SHIPPING_DESCRIPTION,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Shipping Description'
        )->addColumn(
            SubscriptionProfile::PROFILE_CURRENCY_CODE,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Profile Currency Code'
        )->addColumn(
            SubscriptionProfile::TRIAL_LENGTH,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            5,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Trial Length'
        )->addColumn(
            SubscriptionProfile::TRIAL_LENGTH_UNIT,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Trial Length Unit'
        )->addColumn(
            SubscriptionProfile::IS_VIRTUAL,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['unsigned' => true, 'nullable' => false, 'default' => '0'],
            'Is virtual'
        )->addColumn(
            SubscriptionProfile::TOKEN_HASH,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            128,
            ['nullable' => true, 'default' => null],
            'Token Hash'
        )->addColumn(
            SubscriptionProfile::PAYMENT_ADDITIONAL_INFO,
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => true, 'default' => null],
            'Payment Additional Info'
        )->addColumn(
            SubscriptionProfile::GENERATE_QUOTES_STATE,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['nullable' => false, 'default' => '0'],
            'Generate quotes state'
        )->addColumn(
            SubscriptionProfile::NEED_RECOLLECT,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['nullable' => false, 'default' => '0'],
            'Need Recollect'
        )->addColumn(
            SubscriptionProfile::CANCEL_BEFORE_NEXT_CYCLE,
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['nullable' => false, 'default' => '0'],
            'Cancel before next cycle'
        )->addColumn(
            SubscriptionProfile::CREATED_AT,
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Created at'
        )->addColumn(
            SubscriptionProfile::UPDATED_AT,
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Updated at'
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
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
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
    }

    /**
     * Add subscription_profile_id column to sales_order_grid table.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addSubscriptionProfileIdColumnToSalesOrderGrid(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_grid'),
            'subscription_profile_id',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'subscription_profile_id'
            ]
        );
    }
}
