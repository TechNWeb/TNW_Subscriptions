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
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
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
     * Module uninstall code
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        $setup->startSetup();

        $this->dropTables($setup);

        $this->removeProductAttributes($setup);

        $this->removeConfig($setup);

        $this->removeSubscriptionProfileAttributesAndEntityType($setup);

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
            'tnw_subscriptions_subscription_profile_entity_varchar',
            'tnw_subscriptions_subscription_profile_entity_text',
            'tnw_subscriptions_subscription_profile_entity_int',
            'tnw_subscriptions_subscription_profile_entity_decimal',
            'tnw_subscriptions_subscription_profile_entity_datetime',
            'tnw_subscriptions_subscription_profile_entity',
        ];

        foreach ($tnwTables as $tnwTable) {
            $setup->getConnection()
                ->dropTable($setup->getTable($tnwTable));
        }

        return $this;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return $this
     */
    protected function removeProductAttributes(SchemaSetupInterface $setup)
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
     * Remove Subscription Profile Attributes.
     *
     * @param $setup
     * @return $this
     */
    private function removeSubscriptionProfileAttributesAndEntityType($setup)
    {
        $tnwSubscriptionProfileAttributes = [
            'customer_id',
            'billing_frequency_id',
            'label',
            'unit',
            'website_id',
            'status',
            'frequency',
            'engine_code',
            'shipping_address_id',
            'billing_address_id',
            'created_at',
            'updated_at',
        ];

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();

        foreach ($tnwSubscriptionProfileAttributes as $tnwSubscriptionProfileAttribute) {
            $eavSetup->removeAttribute(SubscriptionProfile::ENTITY, $tnwSubscriptionProfileAttribute);
        }

        $eavSetup->removeEntityType(SubscriptionProfile::ENTITY);

        return $this;
    }
}
