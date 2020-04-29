<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Framework\Api\SearchCriteriaBuilder;
use TNW\Subscriptions\Model\SubscriptionProfile;
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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SubscriptionSetupFactory
     */
    private $subscriptionSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param SubscriptionSetupFactory $subscriptionSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductAttributeRepositoryInterface $attributeRepository,
        SubscriptionSetupFactory $subscriptionSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
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

        if (version_compare($context->getVersion(), "2.0.19", "<")) {
            $this->removeRequiredFlagFromProductAttrubutes($eavSetup);
        }

        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            $this->updateDonationProductAttributes($eavSetup);
            $this->addScheduleAttribute($eavSetup);
            $this->upgradeEntities($setup);
            $this->dropProfileAttributes($eavSetup);
        }

        if (version_compare($context->getVersion(), '2.1.7', '<')) {
            $this->fillProfileItemSalesItemTable($setup);
        }

        if (version_compare($context->getVersion(), '2.1.15', '<')) {
            $this->upgradeProfileProductCustomOptions($setup);
        }

        if (version_compare($context->getVersion(), '2.1.18', '<')) {
            $this->addNextPaymentAttributes($eavSetup);
        }

        if (version_compare($context->getVersion(), '2.2.13', '<')) {
            $this->addHideQtyProductAttributes($eavSetup);
        }

        if (version_compare($context->getVersion(), '2.2.38', '<')) {
            $this->removeConfigValue($setup, 'tnw_subscriptions_profile_options/general/shipping_fallback');
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
     * Adds 'hide qty' product attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function addHideQtyProductAttributes(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_HIDE_QTY,
            [
                'type' => 'int',
                'label' => 'Hide Qty',
                'input' => 'boolean',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => 'simple,virtual,downloadable,configurable',
                'group' => 'Subscription Options',
                'sort_order' => 125,
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

    /**
     * Update attributes for Donation product type.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function updateDonationProductAttributes(EavSetup $eavSetup)
    {
        $excludedAttributes = [
            Attribute::SUBSCRIPTION_TRIAL_STATUS,
            Attribute::SUBSCRIPTION_TRIAL_LENGTH,
            Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
            Attribute::SUBSCRIPTION_TRIAL_PRICE,
            Attribute::SUBSCRIPTION_TRIAL_START_DATE,
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
            Attribute::SUBSCRIPTION_HIDE_QTY,
            Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
        ];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('additional_table.apply_to', '%virtual%', 'like')
            ->addFilter('attribute_code', $excludedAttributes, 'nin')
            ->create();

        $productAttributes = $this->attributeRepository->getList($searchCriteria)->getItems();

        foreach ($productAttributes as $attribute) {
            $applyTo = $attribute->getApplyTo();
            if (is_array($applyTo) && !in_array('donation', $applyTo)) {
                $applyTo[] = 'donation';

                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attribute->getAttributeId(),
                    'apply_to',
                    implode(',', $applyTo)
                );
            }
        }
    }

    /**
     * Add 'schedule' product attribute.
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    private function addScheduleAttribute(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_SCHEDULE,
            [
                'type' => 'int',
                'backend' => '',
                'frontend' => '',
                'label' => 'Schedule',
                'input' => 'select',
                'class' => '',
                'source' => \TNW\Subscriptions\Model\Config\Source\ScheduleType::class,
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
                'apply_to' => 'donation',
                'system' => 1,
                'group' => 'Subscription Options',
                'sort_order' => 150,
            ]
        );
    }

    /**
     * Drop attributes in subscription profile entity
     *
     * @param $eavSetup EavSetup
     * @return void
     */
    private function dropProfileAttributes(EavSetup $eavSetup)
    {
        $eavSetup->removeAttribute(
            SubscriptionProfile::ENTITY,
            SubscriptionProfileInterface::ENGINE_CODE
        );
        $eavSetup->removeAttribute(
            SubscriptionProfile::ENTITY,
            SubscriptionProfileInterface::TOKEN_HASH
        );
        $eavSetup->removeAttribute(
            SubscriptionProfile::ENTITY,
            SubscriptionProfileInterface::PAYMENT_ADDITIONAL_INFO
        );
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return void
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

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function fillProfileItemSalesItemTable(ModuleDataSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $select = $connection->select()
            ->from(
                ['profileItem' => $setup->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                ['profile_item_id' => 'entity_id']
            )
            ->joinInner(
                ['relation' => $setup->getTable('tnw_subscriptions_subscription_profile_order')],
                'profileItem.subscription_profile_id = relation.subscription_profile_id',
                []
            )
            ->joinInner(
                ['quoteItem' => $setup->getTable('quote_item')],
                'relation.magento_quote_id = quoteItem.quote_id AND profileItem.magento_product_id = quoteItem.product_id AND profileItem.qty = quoteItem.qty',
                ['quote_item_id' => 'item_id']
            )
            ->joinInner(
                ['orderItem' => $setup->getTable('sales_order_item')],
                'relation.magento_order_id = orderItem.order_id AND profileItem.magento_product_id = orderItem.product_id AND profileItem.qty = orderItem.qty_ordered',
                ['order_item_id' => 'item_id']
            );

        $query = $connection->insertFromSelect($select, $setup->getTable('tnw_subscriptions_profile_item_sales_item'));
        $connection->query($query);
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function upgradeProfileProductCustomOptions(ModuleDataSetupInterface $setup)
    {
        $productSubscriptionTable = $setup->getTable('tnw_subscriptions_product_subscription_profile_entity');

        $connection = $setup->getConnection();
        $select = $connection->select()
            ->from($productSubscriptionTable, ['entity_id', 'custom_options'])
            ->where('custom_options IS NOT NULL');

        foreach ($connection->fetchPairs($select) as $entityId => $options) {
            try {
                $options = \Zend_Json::decode($options);
            } catch (\Exception $e) {
                continue;
            }

            if (isset($options['info_buyRequest'])) {
                continue;
            }

            $connection->update(
                $productSubscriptionTable,
                ['custom_options' => \Zend_Json::encode(['info_buyRequest' => ['super_attribute' => $options]])],
                $connection->prepareSqlCondition('entity_id', $entityId)
            );
        }
    }

    /**
     * Adds attributes for the next payment calculation.
     *
     * @param EavSetup $eavSetup
     */
    private function addNextPaymentAttributes(EavSetup $eavSetup)
    {
        $nextPaymentAttributes = [
            'subtotal',
            'shipping',
            'discount',
            'tax',
            'grand_total'
        ];

        foreach ($nextPaymentAttributes as $nextPaymentAttribute) {

            $eavSetup->addAttribute(
                SubscriptionProfile::ENTITY,
                $nextPaymentAttribute,
                [
                    'type' => 'static',
                    'frontend' => '',
                    'label' => $nextPaymentAttribute,
                    'input' => '',
                    'class' => '',
                    'visible' => false,
                    'required' => false,
                    'user_defined' => false,
                    'default' => null,
                    'unique' => false,
                    'system' => 1,
                    'sort_order' => 10,
                ]
            );
        }
    }

    /**
     * Removes Config Value By Path for each store
     *
     * @param ModuleDataSetupInterface $setup
     * @param $path
     */
    protected function removeConfigValue(ModuleDataSetupInterface $setup, $path)
    {
        $configTable = $setup->getTable('core_config_data');
        $connection = $setup->getConnection();
        $connection->delete(
            $configTable,
            $setup->getConnection()->prepareSqlCondition('path', $path)
        );
    }
}
