<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup;

use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

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
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), "2.0.3", "<")) {
            /** @var SubscriptionSetup $subscriptionSetup */
            $subscriptionSetup = $this->subscriptionSetupFactory->create(['setup' => $setup]);
            $subscriptionSetup->installEntities();
            $subscriptionSetup->addAttributeGroup(
                SubscriptionProfile::ENTITY,
                'Default',
                'Additional information'
            );
        }

        if (version_compare($context->getVersion(), "2.0.4", "<")) {
            $eavSetup->updateAttribute(
                Product::ENTITY,
                'tnw_subscr_trial_price',
                'backend_type',
                'decimal'
            );
            $eavSetup->updateAttribute(
                Product::ENTITY,
                'tnw_subscr_trial_price',
                'backend_model',
                'Magento\Catalog\Model\Product\Attribute\Backend\Price'
            );
            $eavSetup->updateAttribute(
                Product::ENTITY,
                'tnw_subscr_trial_price',
                'frontend_input',
                'price'
            );

            $eavSetup->updateAttribute(
                Product::ENTITY,
                'tnw_subscr_discount_amount',
                'backend_type',
                'decimal'
            );
            $eavSetup->updateAttribute(
                Product::ENTITY,
                'tnw_subscr_discount_amount',
                'backend_model',
                'Magento\Catalog\Model\Product\Attribute\Backend\Price'
            );
            $eavSetup->updateAttribute(
                Product::ENTITY,
                'tnw_subscr_discount_amount',
                'frontend_input',
                'price'
            );
        }

        if (version_compare($context->getVersion(), "2.0.5", "<")) {
            /** @var SubscriptionSetup $subscriptionSetup */
            $subscriptionSetup = $this->subscriptionSetupFactory->create(['setup' => $setup]);
            $subscriptionSetup->installEntities();
            $subscriptionSetup->addAttributeGroup(
                ProductSubscriptionProfile::ENTITY,
                'Default',
                'Additional information'
            );

            if ($setup->tableExists('tnw_subscriptions_product_subscription_profile')) {
                $select = $setup->getConnection()->select()
                    ->from($setup->getTable('tnw_subscriptions_product_subscription_profile'));
                $select = $setup->getConnection()->insertFromSelect(
                    $select,
                    $setup->getTable(ProductSubscriptionProfile::ENTITY_TABLE),
                    [
                        'entity_id',
                        'subscription_profile_id',
                        'magento_product_id',
                        'price',
                        'initial_fee',
                        'qty',
                        'purchase_type',
                        'trial_status',
                        'trial_length',
                        'trial_length_unit',
                        'trial_price',
                        'trial_start_date',
                        'start_date',
                        'lock_product_price_status',
                        'offer_flat_discount_status',
                        'discount_amount',
                        'discount_type',
                    ]
                );
                $setup->getConnection()->query($select);
                $setup->getConnection()->dropTable('tnw_subscriptions_product_subscription_profile');
            }
        }

        $setup->endSetup();
    }
}
