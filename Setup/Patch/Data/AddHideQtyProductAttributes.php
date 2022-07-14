<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Class AddHideQtyProductAttributes - data patch
 */
class AddHideQtyProductAttributes implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $setup
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $setup
    ) {
        $this->setup = $setup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [AddNextPaymentAttributes::class];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        $this->setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $eavSetup->addAttribute(
            Product::ENTITY,
            Attribute::SUBSCRIPTION_HIDE_QTY,
            array(
                'type' => 'int',
                'label' => 'Hide Qty',
                'input' => 'boolean',
                'source' => Boolean::class,
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
            )
        );

        $this->setup->endSetup();
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '2.2.13';
    }
}
