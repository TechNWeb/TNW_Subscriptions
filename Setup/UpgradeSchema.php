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
use TNW\Subscriptions\Api\Data\CustomerProductHistoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfilePaymentInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Api\Data\ProductBillingFrequencyInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;

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
        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $this->addInvoiceItemExtensionAttributeTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.4', '<')) {
            $this->addCreditmemoItemExtensionAttributeTable($setup);
            $this->addInvoicedAndRefundedInitialFeeColumnsToOrderItemExtAtrTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $this->removeUniqueProductEntityKey($setup);
        }

        if (version_compare($context->getVersion(), '2.1.0', '<')) {
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
            $this->createProfileItemSalesItemTable($setup);
            $this->createOrderExtensionTable($setup);
            $this->createCreditMemoExtensionTable($setup);
        }

        if (version_compare($context->getVersion(), '2.1.18', '<')) {
            $this->addNextPaymentAttributes($setup);
        }

        if (version_compare($context->getVersion(), '2.2.6', '<')) {
            $this->updateNextPaymentAttributes($setup);
        }

        if (version_compare($context->getVersion(), '2.2.12', '<')) {
            $this->addPaymentSubscriptionColumn($setup);
        }

        if (version_compare($context->getVersion(), '2.2.18', '<')) {
            $this->removePaymentSubscriptionColumn($setup);
        }

        if (version_compare($context->getVersion(), '2.2.34', '<')) {
            $this->updateItemIdColumnToOrderItemExtAtrTable($setup);
        }

        if (version_compare($context->getVersion(), '2.2.44', '<')) {
            $this->addOriginalStartDateColumnToSubscriptionProfile($setup);
        }

        if (version_compare($context->getVersion(), '2.2.45', '<')) {
            $this->addCheckSendMailColumn($setup);
        }

        if (version_compare($context->getVersion(), '2.2.49', '<')) {
            $billingFreqTable = $setup->getTable(
                ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
            );
            $productTable = $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE);
            $this->deleteForeignKeys(
                $setup,
                [
                    $billingFreqTable => $setup->getConnection()->getForeignKeyName(
                        $billingFreqTable,
                        'magento_product_id',
                        'catalog_product_entity',
                        'entity_id'
                    ),
                    $productTable => $setup->getConnection()->getForeignKeyName(
                        $productTable,
                        'magento_product_id',
                        'catalog_product_entity',
                        'entity_id'
                    ),
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.3.15', '<')) {
            $this->addSkipBillingCyclesColumn($setup);
        }

        if (version_compare($context->getVersion(), '2.3.16', '<')) {
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable(
                        ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
                    ),
                    'is_disabled',
                    [
                        'type' => Table::TYPE_BOOLEAN,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Is Disabled'
                    ]
                );
        }

        if (version_compare($context->getVersion(), '2.3.26', '<')) {
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
            $this->addCouponCodeToSubscriptionProfile($setup);
        }

        if (version_compare($context->getVersion(), '2.3.28', '<')) {
            $this->addCustomerProductsHistoryTable($setup);
        }

        if (version_compare($context->getVersion(), '2.3.36', '<')) {
            $this->addColumnForSubscriptionGrid($setup);
            $this->addRecurringInstallmentDates($setup);
            $setup->getConnection()->addColumn(
                $setup->getTable('tnw_subscriptions_subscription_profile_entity'),
                'subscription_total_static_billing_cycles',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Static total billing cycles ',
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.3.37', '<')) {
            $this->addRecurringInstallmentDatesSalesOrder($setup);
        }

        if (version_compare($context->getVersion(), '2.3.36', '<')) {
            $this->addVaultThreeDSecureFields($setup);
            $this->addRebillTable($setup);
        }

        if (version_compare($context->getVersion(), '2.3.38', '<')) {
            $this->addSubscriptionProfileProfitTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param $data
     */
    private function deleteForeignKeys(SchemaSetupInterface $setup, $data)
    {
        $setup->startSetup();
        foreach ($data as $table => $fkName) {
            $setup->getConnection()->dropForeignKey(
                $table,
                $fkName
            );
        }
        $setup->endSetup();
    }

    /**
     * Create table 'tnw_subscriptions_invoice_item_extension_entity'.
     *
     * @param SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    private function addInvoiceItemExtensionAttributeTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_invoice_item_extension_entity'))
            ->addColumn('item_id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'Magento quote item ID')
            ->addColumn('subs_initial_fee', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000'
            ], 'Initial fee')
            ->addColumn('base_subs_initial_fee', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000'
            ], 'Base initial fee');

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addVaultThreeDSecureFields(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('vault_payment_token');

        $setup->getConnection()
            ->addColumn($table, 'liability_shift_possible', [
                'type' => Table::TYPE_SMALLINT,
                'default' => '0',
                'comment' => 'Is Liability Shift Possible'
            ]);
        $setup->getConnection()
            ->addColumn($table, 'liability_shifted', [
                'type' => Table::TYPE_SMALLINT,
                'default' => '0',
                'comment' => 'Is Liability Shifted'
            ]);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function addRebillTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_rebill_profiles'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Entity ID'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Customer id whose message it is'
            )->addColumn(
                'subscription_profiles',
                Table::TYPE_TEXT,
                256,
                ['nullable' => false],
                'Subscription Profiles To Process'
            )->addColumn(
                'queues',
                Table::TYPE_TEXT,
                256,
                ['nullable' => false],
                'Subscription Queues To Verify'
            )->addColumn(
                'token',
                Table::TYPE_TEXT,
                256,
                ['nullable' => false],
                'Token'
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     * Adds 'subs_initial_fee_invoiced', 'base_subs_initial_fee_invoiced',
     * 'subs_initial_fee_refunded' and 'base_subs_initial_fee_refunded' columns
     * to table 'tnw_subscriptions_order_item_extension_entity'.
     *
     * @param SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    private function addCreditmemoItemExtensionAttributeTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_creditmemo_item_extension_entity'))
            ->addColumn('item_id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'Magento quote item ID')
            ->addColumn('subs_initial_fee', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000'
            ], 'Initial fee')
            ->addColumn('base_subs_initial_fee', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000'
            ], 'Base initial fee');

        $setup->getConnection()->createTable($table);
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
        $table = $setup->getTable('tnw_subscriptions_order_item_extension_entity');

        $setup->getConnection()
            ->addColumn($table, 'subs_initial_fee_invoiced', [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Invoiced initial fee'
            ]);

        $setup->getConnection()
            ->addColumn($table, 'base_subs_initial_fee_invoiced', [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Base invoiced initial fee'
            ]);

        $setup->getConnection()
            ->addColumn($table, 'subs_initial_fee_refunded', [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Refunded initial fee'
            ]);

        $setup->getConnection()
            ->addColumn($table, 'base_subs_initial_fee_refunded', [
                'type' => Table::TYPE_DECIMAL,
                'length' => '12,4',
                'nullable' => false,
                'default' => '0.0000',
                'comment' => 'Base refunded initial fee'
            ]);
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
            $setup->getTable('tnw_subscriptions_product_subscription_profile_entity'),
            $setup->getIdxName(
                $setup->getTable('tnw_subscriptions_product_subscription_profile_entity'),
                [
                    'subscription_profile_id',
                    'magento_product_id'
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
            ->newTable($setup->getTable('tnw_subscriptions_product_subscription_profile_eav_attribute'))
            ->addColumn('attribute_id', Table::TYPE_SMALLINT, null, [
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'Attribute ID')
            ->addColumn('is_visible_on_front', Table::TYPE_SMALLINT, null, [
                'unsigned' => true,
                'nullable' => false,
                'default' => '0'
            ], 'Is Visible On Front')
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
                Table::ACTION_CASCADE
            )
            ->setComment('Product Subscription Profile EAV Attribute Table');

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
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_subscription_profile_payment'))
            ->addColumn('payment_id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'nullable' => false,
                'primary' => true,
                'unsigned' => true
            ], 'Payment ID')
            ->addColumn('subscription_profile_id', Table::TYPE_INTEGER, null, [
                'nullable' => false,
                'unsigned' => true
            ], 'Profile ID')
            ->addColumn('engine_code', Table::TYPE_TEXT, 255, [
                'nullable' => false
            ], 'Engine Code')
            ->addColumn('token_hash', Table::TYPE_TEXT, 128, [
                'nullable' => true,
                'default' => null
            ], 'Token Hash')
            ->addColumn('payment_additional_info', Table::TYPE_TEXT, null, [
                'nullable' => true,
                'default' => null
            ], 'Payment Additional Info')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT
            ], 'Created at')
            ->addColumn('updated_at', Table::TYPE_TIMESTAMP, null, [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE
            ], 'Updated at')
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_subscription_profile_payment',
                    'subscription_profile_id',
                    'tnw_subscriptions_subscription_profile_entity',
                    'entity_id'
                ),
                'subscription_profile_id',
                $setup->getTable('tnw_subscriptions_subscription_profile_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function dropProfileColumns(SchemaSetupInterface $setup)
    {
        $subscriptionTable = $setup->getTable('tnw_subscriptions_subscription_profile_entity');
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
            ->newTable($setup->getTable('tnw_subscriptions_message'))
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
                $setup->getFkName(
                    'tnw_subscriptions_message',
                    'website_id',
                    'store_website',
                    'website_id'
                ),
                'website_id',
                $setup->getTable('store_website'),
                'website_id',
                Table::ACTION_CASCADE
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addIsSubscriptionColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()
            ->addColumn($setup->getTable('quote'), 'is_tnw_subscription', [
                'type' => Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => 0,
                'comment' => 'Is TNW Subscription Quote'
            ]);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addPaymentSubscriptionColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()
            ->addColumn($setup->getTable('quote'), 'subscription_payment_data', [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'default' => '',
                'comment' => 'Subscription Payment Data'
            ]);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function removePaymentSubscriptionColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()
            ->dropColumn($setup->getTable('quote'), 'subscription_payment_data');
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

    /**
     * @param SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    private function createProfileItemSalesItemTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_profile_item_sales_item'))
            ->addColumn('profile_item_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ], 'Profile Item')
            ->addColumn('quote_item_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ], 'Quote Item')
            ->addColumn('order_item_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => true,
                'default' => null,
            ], 'Order Item')
            ->addIndex(
                $setup->getIdxName(
                    'tnw_subscriptions_profile_item_sales_item',
                    ['profile_item_id', 'quote_item_id', 'order_item_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['profile_item_id', 'quote_item_id', 'order_item_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_profile_item_sales_item',
                    'profile_item_id',
                    'tnw_subscriptions_product_subscription_profile_entity',
                    'entity_id'
                ),
                'profile_item_id',
                $setup->getTable('tnw_subscriptions_product_subscription_profile_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_profile_item_sales_item',
                    'quote_item_id',
                    'quote_item',
                    'item_id'
                ),
                'quote_item_id',
                $setup->getTable('quote_item'),
                'item_id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_profile_item_sales_item',
                    'order_item_id',
                    'sales_order_item',
                    'item_id'
                ),
                'order_item_id',
                $setup->getTable('sales_order_item'),
                'item_id',
                Table::ACTION_CASCADE
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    private function createOrderExtensionTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_order_extension_entity'))
            ->addColumn('entity_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Profile Item')
            ->addColumn('subscription_initial_fee_refunded', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000',
            ], 'Refunded initial fee')
            ->addColumn('base_subscription_initial_fee_refunded', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000',
            ], 'Base refunded initial fee')
            ->addIndex(
                $setup->getIdxName(
                    'tnw_subscriptions_order_extension_entity',
                    ['entity_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_order_extension_entity',
                    'entity_id',
                    'sales_order',
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable('sales_order'),
                'entity_id',
                Table::ACTION_CASCADE
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     *
     * @throws \Zend_Db_Exception
     */
    private function createCreditMemoExtensionTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_creditmemo_extension_entity'))
            ->addColumn('entity_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Profile Item')
            ->addColumn('subscription_initial_fee', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000',
            ], 'Initial fee')
            ->addColumn('base_subscription_initial_fee', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => false,
                'default' => '0.0000',
            ], 'Base initial fee')
            ->addIndex(
                $setup->getIdxName(
                    'tnw_subscriptions_creditmemo_extension_entity',
                    ['entity_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['entity_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addForeignKey(
                $setup->getFkName(
                    'tnw_subscriptions_creditmemo_extension_entity',
                    'entity_id',
                    'sales_creditmemo',
                    'entity_id'
                ),
                'entity_id',
                $setup->getTable('sales_creditmemo'),
                'entity_id',
                Table::ACTION_CASCADE
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addNextPaymentAttributes(SchemaSetupInterface $setup)
    {
        $nextPaymentAttributes = [
            'subtotal',
            'shipping',
            'discount',
            'tax',
            'grand_total'
        ];

        foreach ($nextPaymentAttributes as $nextPaymentAttribute) {

            $setup->getConnection()
                ->addColumn($setup->getTable(
                    SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY
                ), $nextPaymentAttribute, [
                    'type' => Table::TYPE_DECIMAL,
                    'nullable' => false,
                    'default' => 0,
                    'comment' => $nextPaymentAttribute
                ]);
        }
    }

    /**
     * Add column for check sent mail
     *
     * @param SchemaSetupInterface $setup
     */
    private function addCheckSendMailColumn(SchemaSetupInterface $setup)
    {
        $setup->getConnection()
            ->addColumn(
                $setup->getTable(SubscriptionProfilePaymentInterface::SUBSCRIPTIONS_PROFILE_PAYMENT_TABLE),
                'sent_mail',
                [
                'type' => Table::TYPE_BOOLEAN,
                'nullable' => false,
                'default' => 0,
                'comment' => 'sent_mail'

                ]
            );
    }

    /**
     * Updates attributes for the next payment calculation.
     *
     * @param SchemaSetupInterface $setup
     */
    private function updateNextPaymentAttributes(SchemaSetupInterface $setup)
    {
        $nextPaymentAttributes = [
            'subtotal',
            'shipping',
            'discount',
            'tax',
            'grand_total'
        ];

        foreach ($nextPaymentAttributes as $nextPaymentAttribute) {
            $setup->getConnection()
                ->changeColumn(
                    $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY),
                    $nextPaymentAttribute,
                    $nextPaymentAttribute,
                    [
                        'type' => Table::TYPE_DECIMAL,
                        'nullable' => false,
                        'scale' => 4,
                        'precision' => 20,
                        'default' => 0,
                        'comment' => $nextPaymentAttribute
                    ]
                );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function updateItemIdColumnToOrderItemExtAtrTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->changeColumn(
            $setup->getTable('tnw_subscriptions_order_item_extension_entity'),
            'item_id',
            'magento_item_id',
            [
                'type' => Table::TYPE_INTEGER,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
                'comment' => 'Magento order item ID'
            ]
        );
    }

    /**
     * Add Original Start Date column to Subscription Profile Entity.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addOriginalStartDateColumnToSubscriptionProfile(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY);
        $connection = $setup->getConnection();
        $connection->addColumn(
            $table,
            SubscriptionProfile::ORIGINAL_START_DATE,
            [
                'type' => Table::TYPE_DATETIME,
                'comment' => 'Original Start Date'
            ]
        );
        $select = $connection->select()
            ->from($table)
            ->columns([
                'entity_id',
                SubscriptionProfile::START_DATE
            ]);
        $rows = $connection->fetchAssoc($select);
        foreach ($rows as $row) {
            $connection->update(
                $table,
                [SubscriptionProfile::ORIGINAL_START_DATE => $row[SubscriptionProfile::START_DATE]],
                ['entity_id = ?' => $row['entity_id']]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addSkipBillingCyclesColumn(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY);
        $setup->getConnection()->addColumn(
            $table,
            SubscriptionProfile::BILLING_CYCLES_TO_SKIP,
            [
                'type' => Table::TYPE_SMALLINT,
                'comment' => 'Billing Cycles To Skip',
                'default' => 0,
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addCouponCodeToSubscriptionProfile(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY);
        $setup->getConnection()->addColumn(
            $table,
            SubscriptionProfile::COUPON_CODE,
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'Coupon Code',
                'default' => null,
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Zend_Db_Exception
     */
    private function addCustomerProductsHistoryTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable(CustomerProductHistoryInterface::CUSTOMER_PRODUCT_HISTORY_TABLE))
            ->addColumn(CustomerProductHistoryInterface::ID, Table::TYPE_INTEGER, null, [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true
            ], 'ID')
            ->addColumn(CustomerProductHistoryInterface::SUBSCRIPTION_PROFILE_ID, Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Subscription Profile ID')
            ->addColumn(CustomerProductHistoryInterface::CUSTOMER_ID, Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Customer ID')
            ->addColumn(CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID, Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => false,
            ], 'Magento product ID')
            ->addIndex(
                $setup->getIdxName(
                    CustomerProductHistoryInterface::CUSTOMER_PRODUCT_HISTORY_TABLE,
                    [
                        CustomerProductHistoryInterface::CUSTOMER_ID,
                        CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID,
                    ]
                ),
                [CustomerProductHistoryInterface::CUSTOMER_ID, CustomerProductHistoryInterface::MAGENTO_PRODUCT_ID]
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     *  add column for store grid subscription data
     *  MPOW-541: Product page: Link to grid with active subscriptions, pre-filtered Grid
     *
     * @param SchemaSetupInterface $setup
     */
    private function addColumnForSubscriptionGrid(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable(
            ProductBillingFrequencyInterface::SUBSCRIPTIONS_PRODUCT_BILLING_FREQUENCY_TABLE
        );
        $setup->getConnection()
            ->addColumn(
                $table,
                'subscription_profile_ids',
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => Table::MAX_TEXT_SIZE,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Store Subscription profiles id for grid on remove BF'
                ]
            );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addRecurringInstallmentDates(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('sales_order_grid');
        $setup->getConnection()->addColumn(
            $table,
            'subscription_paid_installment',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Recurring Paid Installment',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_final_installment_date',
            [
                'type' => Table::TYPE_DATETIME,
                'nullable' => true,
                'default' => null,
                'comment' => 'Recurring Final Installment Date',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_first_installment_date',
            [
                'type' => Table::TYPE_DATETIME,
                'nullable' => true,
                'default' => null,
                'comment' => 'Recurring 1st Installment Date',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_expire_cc',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Will cc expire',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_total_static_billing_cycles',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Static total billing cycles',
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addRecurringInstallmentDatesSalesOrder(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('sales_order');
        $setup->getConnection()->addColumn(
            $table,
            'subscription_paid_installment',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Recurring Paid Installment',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_final_installment_date',
            [
                'type' => Table::TYPE_DATETIME,
                'nullable' => true,
                'default' => null,
                'comment' => 'Recurring Final Installment Date',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_first_installment_date',
            [
                'type' => Table::TYPE_DATETIME,
                'nullable' => true,
                'default' => null,
                'comment' => 'Recurring 1st Installment Date',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_expire_cc',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Will cc expire',
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'subscription_total_static_billing_cycles',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => 'Static total billing cycles',
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function addSubscriptionProfileProfitTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('tnw_subscriptions_profile_profit'))
            ->addColumn('entity_id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'nullable' => false,
                'primary' => true,
                'unsigned' => true
            ], 'Entity ID')
            ->addColumn('profile_id', Table::TYPE_INTEGER, null, [
                'nullable' => true,
                'default' => null
            ], 'Subscription profile ID')
            ->addColumn('profit_type', Table::TYPE_TEXT, '255', [
                'nullable' => true,
                'default' => null
            ], 'Profit type')
            ->addColumn('total_profit', Table::TYPE_DECIMAL, '12,4', [
                'nullable' => true,
                'default' => '0.0000'
            ], 'Total profit');

        $setup->getConnection()->createTable($table);
    }

}
