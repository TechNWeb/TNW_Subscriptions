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
 * Class Uninstall
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
        $this->dropTables($setup);
    }

    /**
     * Removes subscription tables.
     *
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    protected function dropTables(SchemaSetupInterface $setup)
    {
        $tnwTables = [
            'tnw_subscriptions_product_billing_frequency',
            'tnw_subscriptions_subscription_profile_order',
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

            // Profile EAV
            'tnw_subscriptions_subscription_profile_entity_varchar',
            'tnw_subscriptions_subscription_profile_entity_text',
            'tnw_subscriptions_subscription_profile_entity_int',
            'tnw_subscriptions_subscription_profile_entity_decimal',
            'tnw_subscriptions_subscription_profile_entity_datetime',
            'tnw_subscriptions_subscription_profile_entity',

            // Product Profile EAV
            'tnw_subscriptions_product_subscription_profile_entity_varchar',
            'tnw_subscriptions_product_subscription_profile_entity_text',
            'tnw_subscriptions_product_subscription_profile_entity_int',
            'tnw_subscriptions_product_subscription_profile_entity_decimal',
            'tnw_subscriptions_product_subscription_profile_entity_datetime',
            'tnw_subscriptions_product_subscription_profile_entity',
        ];

        foreach ($tnwTables as $tnwTable) {
            $setup->getConnection()->dropTable($setup->getTable($tnwTable));
        }

        return $this;
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
}
