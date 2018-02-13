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
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use TNW\Subscriptions\Model\Product\Attribute;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;

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
     * @var SubscriptionSetupFactory
     */
    private $subscriptionSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        SubscriptionSetupFactory $subscriptionSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->subscriptionSetupFactory = $subscriptionSetupFactory;
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

        if (version_compare($context->getVersion(), "2.0.14", "<")) {
            $this->upgradeEntities($setup);
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
     * @param ModuleDataSetupInterface $setup
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function upgradeEntities(ModuleDataSetupInterface $setup)
    {
        /** @var SubscriptionSetup $subscriptionSetup */
        $subscriptionSetup = $this->subscriptionSetupFactory->create(['setup' => $setup]);

        $entityTypeId = $subscriptionSetup->getEntityTypeId(ProductSubscriptionProfile::ENTITY);
        $select = $setup->getConnection()
            ->select()
            ->from($setup->getTable('eav_attribute'), ['attribute_id'])
            ->where($setup->getConnection()->prepareSqlCondition('entity_type_id', $entityTypeId));

        $query = $setup->getConnection()
            ->insertFromSelect(
                $select,
                $setup->getTable('tnw_subscriptions_product_subscription_profile_eav_attribute'),
                ['attribute_id'],
                AdapterInterface::INSERT_IGNORE
            );

        $setup->getConnection()->query($query);

        $subscriptionSetup->installEntities();
    }
}
