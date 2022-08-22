<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Class Uninstall - object used for extension uninstall
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Module uninstall code.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->removeProductAttributes();
        $this->removeConfig($setup);
        $this->removeEntityAttributesAndType(SubscriptionProfile::ENTITY);
        $this->removeEntityAttributesAndType(ProductSubscriptionProfile::ENTITY);

        $configsToDrop = [
            'tnw_subscriptions%',
            'advanced/modules_disable_output/TNW_Subscriptions',
            'carriers/tnwautoship/%',
        ];

        $tablesToDrop = [
            'tnw_subscriptions_rebill_profiles',
            'tnw_subscriptions_profile_profit',
            'tnw_subscriptions_customer_product_history',
            'tnw_subscriptions_rebill_profiles',
            'tnw_subscriptions_sales_order_aggregated_created',
            'tnw_subscriptions_sales_order_aggregated_updated',
            'tnw_subscriptions_product_billing_frequency',
            'tnw_subscriptions_billing_frequency',
            'tnw_subscriptions_customer_quote',
            'tnw_subscriptions_quote_item_extension_entity',
            'tnw_subscriptions_order_item_extension_entity',
            'tnw_subscriptions_creditmemo_item_extension_entity',
            'tnw_subscriptions_subscription_profile_address',
            'tnw_subscriptions_subscription_profile_message_history',
            'tnw_subscriptions_subscription_profile_queue',
            'tnw_subscriptions_subscription_profile_status_history',
            'tnw_subscriptions_message',
            'tnw_subscriptions_creditmemo_extension_entity',
            'tnw_subscriptions_invoice_item_extension_entity',
            'tnw_subscriptions_subscription_profile_order',
            'tnw_subscriptions_subscription_profile_payment',
            'tnw_subscriptions_profile_item_sales_item',
            'tnw_subscriptions_order_extension_entity',

            // Product Profile EAV
            'tnw_subscriptions_product_subscription_profile_eav_attribute',
            'tnw_subscriptions_product_subscription_profile_entity_varchar',
            'tnw_subscriptions_product_subscription_profile_entity_text',
            'tnw_subscriptions_product_subscription_profile_entity_int',
            'tnw_subscriptions_product_subscription_profile_entity_decimal',
            'tnw_subscriptions_product_subscription_profile_entity_datetime',
            'tnw_subscriptions_product_subscription_profile_entity',

            // Profile EAV
            'tnw_subscriptions_subscription_profile_entity_varchar',
            'tnw_subscriptions_subscription_profile_entity_text',
            'tnw_subscriptions_subscription_profile_entity_int',
            'tnw_subscriptions_subscription_profile_entity_decimal',
            'tnw_subscriptions_subscription_profile_entity_datetime',
            'tnw_subscriptions_subscription_profile_entity',
        ];
        $columnsToDrop = [
            'sales_order_grid' => [
                'subscription_paid_installment',
                'subscription_final_installment_date',
                'subscription_first_installment_date',
                'subscription_expire_cc',
                'subscription_total_static_billing_cycles',
                'subscription_profile_id',
            ],
            'magento_sales_order_grid_archive' => [
                'subscription_paid_installment',
                'subscription_final_installment_date',
                'subscription_first_installment_date',
                'subscription_expire_cc',
                'subscription_total_static_billing_cycles',
                'subscription_profile_id',
            ],
            'sales_order' => [
                'subscription_profile_id',
                'subscription_paid_installment',
                'subscription_final_installment_date',
                'subscription_first_installment_date',
                'subscription_expire_cc',
                'subscription_total_static_billing_cycles',
            ],
            'vault_payment_token' => [
                'liability_shift_possible',
                'liability_shifted',
            ],
            'quote' => [
                'is_tnw_subscription',
            ],
        ];
        $indexesToDrop = [];
        $constraintsToDrop = [];

        $this->dropConfigs($setup, $configsToDrop);
        $this->dropSchema($setup, $constraintsToDrop, $indexesToDrop, $columnsToDrop, $tablesToDrop);
    }

    /**
     * Removes product subscription attributes.
     *
     * @return $this
     */
    protected function removeProductAttributes()
    {
        $tnwProductAttributes = [
            'tnw_subscr_purchase_type',
            'tnw_subscr_lock_product_price',
            'tnw_subscr_offer_flat_discount',
            'tnw_subscr_trial_status',
            'tnw_subscr_trial_length',
            'tnw_subscr_trial_length_unit',
            'tnw_subscr_trial_price',
            'tnw_subscr_trial_start_date',
            'tnw_subscr_start_date',
            'tnw_subscr_discount_amount',
            'tnw_subscr_discount_type',
            'tnw_subscr_unlock_preset_qty',
            'tnw_subscr_hide_qty',
            'tnw_subscr_savings_calculation',
            'tnw_subscr_inf_subscriptions',
        ];

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();

        foreach ($tnwProductAttributes as $tnwProductAttribute) {
            $eavSetup->removeAttribute(Product::ENTITY, $tnwProductAttribute);
        }

        return $this;
    }

    /**
     * Removes subscription config.
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    protected function removeConfig(SchemaSetupInterface $setup)
    {
        $where = $setup->getConnection()->quoteInto('value like ?', 'tnw_subscriptions%');

        $setup->getConnection()->delete($setup->getTable('core_config_data'), $where);

        return $this;
    }

    /**
     * Removes entity attributes and entity type.
     *
     * @param string $entity
     * @return $this
     */
    private function removeEntityAttributesAndType($entity)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();

        /** @var \Magento\Eav\Model\Entity\Type $entityType */
        $entityType = ObjectManager::getInstance()->create(\Magento\Eav\Model\Entity\Type::class);
        $entityType->loadByCode($entity);

        /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $attributeCollection */
        $attributeCollection = $entityType->getAttributeCollection();

        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        foreach ($attributeCollection as $attribute) {
            $eavSetup->removeAttribute($entity, $attribute->getAttributeCode());
        }

        $eavSetup->removeEntityType($entity);

        return $this;
    }

    private function dropConfigs(SchemaSetupInterface $setup, array $configs): void
    {
        array_map(
            function (string $config) use ($setup) {
                $setup->getConnection()->delete(
                    $setup->getTable('core_config_data'),
                    $setup->getConnection()->quoteInto('path like ?', $config)
                );
            },
            $configs
        );
    }

    private function dropSchema(
        SchemaSetupInterface $setup,
        array                $constraintsToDrop,
        array                $indexesToDrop,
        array                $columnsToDrop,
        array                $tablesToDrop
    ): void {
        $this->dropForeignKey($setup, $constraintsToDrop);
        $this->dropIndexes($setup, $indexesToDrop);
        $this->dropColumns($setup, $columnsToDrop);
        $this->dropTables($setup, $tablesToDrop);
    }

    private function dropForeignKey(SchemaSetupInterface $setup, array $constraintsData): void
    {
        $filteredData = array_filter(
            $constraintsData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $constraints, string $table) use ($setup) {
                array_map(
                    function (string $constraint) use ($setup, $table) {
                        $setup->getConnection()->dropForeignKey($setup->getTable($table), $constraint);
                    },
                    $constraints
                );
            }
        );
    }

    private function dropIndexes(SchemaSetupInterface $setup, array $indexesData): void
    {
        $filteredData = array_filter(
            $indexesData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $indexes, string $table) use ($setup) {
                array_map(
                    function (string $index) use ($setup, $table) {
                        $setup->getConnection()->dropIndex($setup->getTable($table), $index);
                    },
                    $indexes
                );
            }
        );
    }

    private function dropColumns(SchemaSetupInterface $setup, array $columnsData): void
    {
        $filteredData = array_filter(
            $columnsData,
            function (string $table) use ($setup) {
                return $setup->getConnection()->isTableExists($setup->getTable($table));
            },
            ARRAY_FILTER_USE_KEY
        );
        array_walk(
            $filteredData,
            function (array $columns, string $table) use ($setup) {
                array_map(
                    function (string $column) use ($setup, $table) {
                        $setup->getConnection()->dropColumn($setup->getTable($table), $column);
                    },
                    $columns
                );
            }
        );
    }

    private function dropTables(SchemaSetupInterface $setup, array $tables): void
    {
        array_map(
            function (string $table) use ($setup) {
                $setup->getConnection()->dropTable($setup->getTable($table));
            },
            $tables
        );
    }
}
