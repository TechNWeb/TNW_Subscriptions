<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile;

class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var SubscriptionSetupFactory
     */
    private $subscriptionSetupFactory;

    /**
     * InstallData constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param SubscriptionSetupFactory $subscriptionSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        SubscriptionSetupFactory $subscriptionSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->subscriptionSetupFactory = $subscriptionSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $this->addProductAttributes($eavSetup);
        $this->addEntitiesAndAttributeGroups($setup);
    }

    /**
     * Adds new products attributes.
     *
     * @param $eavSetup
     */
    private function addProductAttributes($eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_PURCHASE_TYPE,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Available For',
                'input' => 'select',
                'class' => '',
                'source' => 'TNW\Subscriptions\Model\Config\Source\PurchaseType',
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
                'group' => 'Subscription options',
                'sort_order' => 10,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_TRIAL_STATUS,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Is trial offered',
                'input' => 'boolean',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
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
                'group' => 'Subscription options',
                'sort_order' => 20,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_TRIAL_LENGTH,
            [
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Trial length',
                'input' => 'text',
                'class' => '',
                'source' => '',
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
                'group' => 'Subscription options',
                'sort_order' => 30,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Trial length Unit',
                'input' => 'select',
                'class' => '',
                'source' => 'TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType',
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
                'group' => 'Subscription options',
                'sort_order' => 40,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_TRIAL_PRICE,
            [
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Trial price',
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
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
                'group' => 'Subscription options',
                'backend_type' => 'decimal',
                'backend_model' => 'Magento\Catalog\Model\Product\Attribute\Backend\Price',
                'frontend_input' => 'price',
                'sort_order' => 50,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT,
            [
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Discount amount',
                'input' => 'text',
                'class' => '',
                'source' => '',
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
                'group' => 'Subscription options',
                'backend_type' => 'decimal',
                'backend_model' => 'TNW\Subscriptions\Model\Backend\Product\Attribute\DiscountAmount',
                'frontend_input' => 'price',
                'frontend_class' => 'discount-less-then-price',
                'sort_order' => 100,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_TRIAL_START_DATE,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Trial start date',
                'input' => 'select',
                'class' => '',
                'source' => 'TNW\Subscriptions\Model\Config\Source\StartDateType',
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
                'group' => 'Subscription options',
                'sort_order' => 60,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_START_DATE,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Start date',
                'input' => 'select',
                'class' => '',
                'source' => 'TNW\Subscriptions\Model\Config\Source\StartDateType',
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
                'group' => 'Subscription options',
                'sort_order' => 70,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Lock product price',
                'input' => 'boolean',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
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
                'group' => 'Subscription options',
                'sort_order' => 80,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Offer flat discount',
                'input' => 'boolean',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
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
                'group' => 'Subscription options',
                'sort_order' => 90,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
            [
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Discount type',
                'input' => 'select',
                'class' => '',
                'source' => 'TNW\Subscriptions\Model\Config\Source\DiscountType',
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
                'group' => 'Subscription options',
                'sort_order' => 110,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Unlock preset qty',
                'input' => 'boolean',
                'class' => '',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
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
                'group' => 'Subscription options',
                'sort_order' => 120,
            ]
        );
    }

    /**
     * Creates new eav entities and attribute groups.
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function addEntitiesAndAttributeGroups(ModuleDataSetupInterface $setup)
    {
        /** @var SubscriptionSetup $subscriptionSetup */
        $subscriptionSetup = $this->subscriptionSetupFactory->create(['setup' => $setup]);
        $subscriptionSetup->installEntities();
        $subscriptionSetup->addAttributeGroup(
            SubscriptionProfile::ENTITY,
            'Default',
            'Additional information'
        );
        $subscriptionSetup->addAttributeGroup(
            ProductSubscriptionProfile::ENTITY,
            'Default',
            'Additional information'
        );
    }
}
