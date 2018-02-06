<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Framework\Api\SearchCriteriaBuilder;

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
     * @param EavSetupFactory $eavSetupFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
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
            $this->updateDonationProductAttributes($eavSetup);
            $this->addScheduleAttribute($eavSetup);
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
    private function addScheduleAttribute($eavSetup)
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
                'required' => true,
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
}
