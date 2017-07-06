<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
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

class Uninstall implements UninstallInterface
{

    private $eavSetupFactory;

    /**
     * Constructor
     *
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
        $setup->startSetup();

        $this->dropTables($setup);

        $this->removeProductAttributes();

        $this->removeConfig($setup);

        $this->removeEntityAttributesAndType(SubscriptionProfile::ENTITY);
        $this->removeEntityAttributesAndType(ProductSubscriptionProfile::ENTITY);

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    protected function dropTables(SchemaSetupInterface $setup)
    {
        $tnwTables = [
            'tnw_subscriptions_product_billing_frequency',
            'tnw_subscriptions_subscription_profile_order',
            'tnw_subscriptions_product_subscription_profile',
            'tnw_subscriptions_billing_frequency',
            SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_varchar',
            SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_text',
            SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_int',
            SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_decimal',
            SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY . '_datetime',
            SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY,
            ProductSubscriptionProfile::ENTITY_TABLE . '_varchar',
            ProductSubscriptionProfile::ENTITY_TABLE . '_text',
            ProductSubscriptionProfile::ENTITY_TABLE . '_int',
            ProductSubscriptionProfile::ENTITY_TABLE . '_decimal',
            ProductSubscriptionProfile::ENTITY_TABLE . '_datetime',
            ProductSubscriptionProfile::ENTITY_TABLE,
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
            'tnw_subscr_set_by_merchant',
        ];

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();

        foreach ($tnwProductAttributes as $tnwProductAttribute) {
            $eavSetup->removeAttribute(Product::ENTITY, $tnwProductAttribute);
        }

        return $this;
    }

    /**
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
