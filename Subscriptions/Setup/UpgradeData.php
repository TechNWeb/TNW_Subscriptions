<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Upgrade data for TNW Subscriptions.
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), "2.0.5", "<")) {
            $this->addSavingsCalculatorProductAttributes($eavSetup);
        }

        if (version_compare($context->getVersion(), "2.0.6", "<")) {
            $this->addInfiniteSubscriptionsProductAttributes($eavSetup);
        }

        if (version_compare($context->getVersion(), "2.0.19", "<")) {
            $this->removeRequiredFlagFromProductAttrubutes($eavSetup);
        }


        $setup->endSetup();
    }

    /**
     * Adds 'savings calculator type' product attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function addSavingsCalculatorProductAttributes(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Savings Calculation',
                'input' => 'boolean',
                'class' => '',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => true,
                'user_defined' => true,
                'default' => null,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => 'simple,virtual,downloadable,configurable',
                'system' => 1,
                'group' => 'Subscription Options',
                'sort_order' => 130,
            ]
        );
    }

    /**
     * Adds 'infinite subscriptions' product attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function addInfiniteSubscriptionsProductAttributes(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Infinite Subscriptions',
                'input' => 'boolean',
                'class' => '',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => true,
                'user_defined' => true,
                'default' => null,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => 'simple,virtual,downloadable,configurable',
                'system' => 1,
                'group' => 'Subscription Options',
                'sort_order' => 140,
            ]
        );
    }

    /**
     * Update attributes to avoid problem with disabled module
     *
     * @param EavSetup $eavSetup
     */
    public function removeRequiredFlagFromProductAttrubutes(EavSetup $eavSetup)
    {
        $attributeCodes = [
            'tnw_subscr_purchase_type',
            'tnw_subscr_trial_status',
            'tnw_subscr_trial_length',
            'tnw_subscr_trial_length_unit',
            'tnw_subscr_lock_product_price',
            'tnw_subscr_offer_flat_discount',
            'tnw_subscr_discount_amount',
            'tnw_subscr_discount_type',
            'tnw_subscr_trial_price',
            'tnw_subscr_trial_start_date',
            'tnw_subscr_start_date',
            'tnw_subscr_unlock_preset_qty',
            'tnw_subscr_savings_calculation',
            'tnw_subscr_inf_subscriptions'
        ];

        foreach ($attributeCodes as $attributeCode) {

            $eavSetup->updateAttribute(
                Product::ENTITY,
                $attributeCode,
                'is_required',
                false
            );
        }
    }
}
