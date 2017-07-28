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
use TNW\Subscriptions\Model\ProductSubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile;
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
                    ->from(
                        $setup->getTable('tnw_subscriptions_product_subscription_profile'),
                        [
                            'id',
                            'subscription_profile_id',
                            'magento_product_id',
                            'price',
                            'initial_fee',
                            'qty',
                            'purchase_type',
                            'trial_status',
                            'trial_price',
                            'lock_product_price_status',
                            'offer_flat_discount_status',
                            'discount_amount',
                            'discount_type',
                        ]
                    );
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
                        'trial_price',
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

        if (version_compare($context->getVersion(), "2.0.7", "<")) {
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
                    'used_in_product_listing' => false,
                    'unique' => false,
                    'apply_to' => '',
                    'system' => 1,
                    'group' => 'Subscription options',
                    'sort_order' => 120,
                ]
            );
        }

        //TODO this upgrade of the attributes 'tnw_subscr_trial_price', 'tnw_subscr_discount_amount' has to be moved to install where this attribute is added
        if (version_compare($context->getVersion(), "2.0.8", "<")) {
            $this->updateProductTrialDiscountAttributes($eavSetup);
        }


        if (version_compare($context->getVersion(), "2.0.10", "<")) {
            //TODO don't add this attributes to subscription profile entity
            $subscriptionSetup = $this->subscriptionSetupFactory->create(['setup' => $setup]);
            $profileEntityTypeId = $subscriptionSetup->getEntityTypeId(SubscriptionProfile::ENTITY);
            $subscriptionSetup->removeAttribute($profileEntityTypeId, 'shipping_address_id');
            $subscriptionSetup->removeAttribute($profileEntityTypeId, 'billing_address_id');
            $subscriptionSetup->removeAttribute($profileEntityTypeId, 'label');
            //TODO don't add this attributes to subscription profile product entity
            $profileProductEntityTypeId = $subscriptionSetup->getEntityTypeId(ProductSubscriptionProfile::ENTITY);
            $subscriptionSetup->removeAttribute($profileProductEntityTypeId, 'trial_start_date');
            $subscriptionSetup->removeAttribute($profileProductEntityTypeId, 'start_date');

            //TODO add this attributes to main eav setup
            $subscriptionSetup->addAttribute(
                $profileProductEntityTypeId,
                ProductSubscriptionProfile::SUBSCRIPTION_PROFILE_ID,
                [
                    'type' => 'static',
                    'label' => 'Subscription Profile Id',
                    'input' => 'text',
                    'required' => false,
                    'visible' => false,
                    'sort_order' => 100,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileProductEntityTypeId,
                ProductSubscriptionProfile::MAGENTO_PRODUCT_ID,
                [
                    'type' => 'static',
                    'label' => 'Magento Product Id',
                    'input' => 'text',
                    'required' => false,
                    'visible' => false,
                    'sort_order' => 110,
                ]
            );

            //TODO add this attributes to main eav setup
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::TRIAL_START_DATE,
                [
                    'type' => 'static',
                    'label' => 'Trial Start Date',
                    'input' => 'date',
                    'required' => false,
                    'visible' => true,
                    'sort_order' => 100,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::START_DATE,
                [
                    'type' => 'static',
                    'label' => 'Start Date',
                    'input' => 'date',
                    'required' => false,
                    'visible' => true,
                    'sort_order' => 110
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::TRIAL_LENGTH,
                [
                    'type' => 'static',
                    'label' => 'Trial Length',
                    'input' => 'text',
                    'required' => false,
                    'frontend_class' => 'validate-number',
                    'sort_order' => 120,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::TRIAL_LENGTH_UNIT,
                [
                    'type' => 'static',
                    'label' => 'Trial Length Unit',
                    'input' => 'select',
                    'required' => false,
                    'source' => \TNW\Subscriptions\Model\Config\Source\TrialLengthUnitType::class,
                    'sort_order' => 130,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::TERM,
                [
                    'type' => 'static',
                    'label' => 'Term',
                    'input' => 'text',
                    'required' => false,
                    'frontend_class' => 'validate-number',
                    'sort_order' => 140,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::TOTAL_BILLING_CYCLES,
                [
                    'type' => 'static',
                    'label' => 'Total billing cycles',
                    'input' => 'text',
                    'required' => false,
                    'frontend_class' => 'validate-number',
                    'sort_order' => 150,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::SHIPPING_METHOD,
                [
                    'type' => 'static',
                    'label' => 'Shipping Method',
                    'input' => 'text',
                    'required' => true,
                    'frontend_class' => 'validate-length maximum-length-40',
                    'sort_order' => 160,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::SHIPPING_DESCRIPTION,
                [
                    'type' => 'static',
                    'label' => 'Shipping Description',
                    'input' => 'text',
                    'required' => false,
                    'frontend_class' => 'validate-length maximum-length-255',
                    'sort_order' => 170,
                ]
            );
            $subscriptionSetup->addAttribute(
                $profileEntityTypeId,
                SubscriptionProfile::PROFILE_CURRENCY_CODE,
                [
                    'type' => 'static',
                    'label' => 'Profile currency code',
                    'input' => 'text',
                    'required' => true,
                    'frontend_class' => 'validate-length maximum-length-255',
                    'sort_order' => 170,
                ]
            );
        }

        $setup->endSetup();
    }


    /**
     * @param EavSetup $eavSetup
     */
    private function updateProductTrialDiscountAttributes($eavSetup)
    {
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'tnw_subscr_trial_price',
            'is_required',
            'false'
        );
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'tnw_subscr_discount_amount',
            'frontend_class',
            'discount-less-then-price'
        );
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'tnw_subscr_discount_amount',
            'frontend_class',
            'discount-less-then-price'
        );
        $eavSetup->updateAttribute(
            Product::ENTITY,
            'tnw_subscr_discount_amount',
            'backend_model',
            'TNW\Subscriptions\Model\Backend\Product\Attribute\DiscountAmount'
        );
    }
}
