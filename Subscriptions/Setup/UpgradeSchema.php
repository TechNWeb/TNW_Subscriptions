<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\OrderItemExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Upgrade schema for TNW Subscriptions.
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "2.0.3", "<")) {
            $this->addInvoiceItemExtensionAttributeTable($setup);
        }

        if (version_compare($context->getVersion(), "2.0.4", "<")) {
            $this->addCreditmemoItemExtensionAttributeTable($setup);
            $this->addInvoicedAndRefundedInitialFeeColumnsToOrderItemExtAtrTable($setup);
        }

        if (version_compare($context->getVersion(), "2.0.6", "<")) {
            $this->removeUniqueProductEntityKey($setup);
        }

        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $this->addProductSubscriptionProfileAttributeTable($setup);
            $this->addProfilePaymentTable($setup);
            $this->dropProfileColumns($setup);
        }

        if (version_compare($context->getVersion(), '2.1.1', '<')) {
            $this->addMessageTable($setup);
        }

        if (version_compare($context->getVersion(), '2.1.7', '<')) {
            $this->addIsSubscriptionColumn($setup);
            $this->dropUniqueKeyProfileOrder($setup);
        }

        $setup->endSetup();
    }

    /**
     * Create table 'tnw_subscriptions_invoice_item_extension_entity'.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addInvoiceItemExtensionAttributeTable(SchemaSetupInterface $setup)
    {
        if (!$setup->tableExists(SalesExtensionAttributesInterface::INVOICE_ITEM_EXTENSION_TABLE)) {
            $table = $setup->getConnection()->newTable(
                SalesExtensionAttributesInterface::INVOICE_ITEM_EXTENSION_TABLE
            )->addColumn(
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
    }

    /**
     * Adds 'subs_initial_fee_invoiced', 'base_subs_initial_fee_invoiced',
     * 'subs_initial_fee_refunded' and 'base_subs_initial_fee_refunded' columns
     * to table 'tnw_subscriptions_order_item_extension_entity'.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addCreditmemoItemExtensionAttributeTable(SchemaSetupInterface $setup)
    {
        if (!$setup->tableExists(SalesExtensionAttributesInterface::CREDITMEMO_ITEM_EXTENSION_TABLE)) {
            $table = $setup->getConnection()->newTable(
                SalesExtensionAttributesInterface::CREDITMEMO_ITEM_EXTENSION_TABLE
            )->addColumn(
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
    }

    /**
     * Add new columns to 'tnw_subscriptions_order_item_extension_entity' table.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function addInvoicedAndRefundedInitialFeeColumnsToOrderItemExtAtrTable(
        SchemaSetupInterface $setup
    ) {
        $setup->getConnection()->addColumn(
            $setup->getTable(OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE),
            OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE_INVOICED,
            [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Invoiced initial fee'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable(OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE),
            OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE_INVOICED,
            [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Base invoiced initial fee'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable(OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE),
            OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE_REFUNDED,
            [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Refunded initial fee'
            ]
        );
        $setup->getConnection()->addColumn(
            $setup->getTable(OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE),
            OrderItemExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE_REFUNDED,
            [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Base refunded initial fee'
            ]
        );
    }

    /**
     * Removes unique key from 'tnw_subscriptions_product_subscription_profile_entity' table.
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function removeUniqueProductEntityKey(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->dropIndex(
            $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
            $setup->getIdxName(
                $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                [
                    ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID,
                    ProductSubscriptionProfile::MAGENTO_PRODUCT_ID
                ]
            )
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function addProductSubscriptionProfileAttributeTable(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'customer_eav_attribute'
         */
        $table = $setup->getConnection()
            ->newTable(
                $setup->getTable('tnw_subscriptions_product_subscription_profile_eav_attribute')
            )
            ->addColumn(
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Attribute ID'
            )
            ->addColumn(
                'is_visible_on_front',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                'Is Visible On Front'
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_product_subscription_profile_eav_attribute',
                    'attribute_id',
                    'eav_attribute',
                    'attribute_id'
                ),
                'attribute_id',
                $setup->getTable('eav_attribute'),
                'attribute_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment(
                'Product Subscription Profile EAV Attribute Table'
            );

        $setup->getConnection()
            ->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function addProfilePaymentTable(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable(SubscriptionProfilePaymentInterface::SUBSCRIPTIONS_PROFILE_PAYMENT_TABLE);

        if (!$setup->tableExists($tableName)) {
            $table = $setup->getConnection()->newTable($tableName);

            $table->addColumn(
                SubscriptionProfilePaymentInterface::PAYMENT_ID,
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Payment ID'
            )->addColumn(
                SubscriptionProfilePaymentInterface::PROFILE_ID,
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'Profile ID'
            )->addColumn(
                SubscriptionProfilePaymentInterface::ENGINE_CODE,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Engine Code'
            )->addColumn(
                SubscriptionProfilePaymentInterface::TOKEN_HASH,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                128,
                ['nullable' => true, 'default' => null],
                'Token Hash'
            )->addColumn(
                SubscriptionProfilePaymentInterface::PAYMENT_ADDITIONAL_INFO,
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'Payment Additional Info'
            )->addColumn(
                SubscriptionProfilePaymentInterface::CREATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created at'
            )->addColumn(
                SubscriptionProfilePaymentInterface::UPDATED_AT,
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated at'
            )->addForeignKey(
                $setup->getConnection()->getForeignKeyName(
                    $tableName,
                    SubscriptionProfilePaymentInterface::PROFILE_ID,
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
                    SubscriptionProfile::ID
                ),
                SubscriptionProfilePaymentInterface::PROFILE_ID,
                $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                SubscriptionProfile::ID,
                \Magento\Framework\DB\Adapter\AdapterInterface::FK_ACTION_CASCADE
            );
            $setup->getConnection()->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function dropProfileColumns(SchemaSetupInterface $setup)
    {
        $subscriptionTable = $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY);
        $setup->getConnection()
            ->dropColumn($subscriptionTable, 'engine_code');
        $setup->getConnection()
            ->dropColumn($subscriptionTable, 'token_hash');
        $setup->getConnection()
            ->dropColumn($subscriptionTable, 'payment_additional_info');
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function addMessageTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable('tnw_subscriptions_message')
            ->addColumn('message_id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'Message ID')
            ->addColumn('transaction_uid', Table::TYPE_TEXT, 32, [
                'nullable' => true,
                'default' => null
            ], 'Transaction')
            ->addColumn('level', Table::TYPE_SMALLINT, null, [
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ], 'Level')
            ->addColumn('website_id', Table::TYPE_SMALLINT, null, [
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ], 'Website')
            ->addColumn('message', Table::TYPE_TEXT, '64k', [
                'nullable' => true,
                'default' => null
            ], 'Message')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT
            ], 'Create At')
            ->addIndex(
                $setup->getIdxName('tnw_subscriptions_message', ['website_id']),
                ['website_id']
            )
            ->addForeignKey(
                $setup->getFkName('tnw_subscriptions_message', 'website_id', 'store_website', 'website_id'),
                'website_id', $setup->getTable('store_website'), 'website_id', Table::ACTION_CASCADE
            )
        ;

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addIsSubscriptionColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('quote'),
            'is_tnw_subscription',
            [
                'type' => Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Is TNW Subscription Quote'
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function dropUniqueKeyProfileOrder(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('tnw_subscriptions_subscription_profile_order');
        $setup->getConnection()->dropIndex(
            $tableName,
            $setup->getIdxName($tableName, ['magento_order_id'])
        );
    }
}
