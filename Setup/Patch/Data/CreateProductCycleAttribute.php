<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

/**
 * Class CreateProductCycleAttribute - Data patch
 */
class CreateProductCycleAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var Action
     */
    private $productAction;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * CreateProductCycleAttribute constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Action $productAction
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        Action $productAction,
        StoreManagerInterface $storeManager,
        CollectionFactory $productCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->storeManager = $storeManager;
        $this->productAction = $productAction;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return DataPatchInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        /**
         * Add attributes to the eav/attribute
         */
        $eavSetup->addAttribute(
            Product::ENTITY,
            'billing_cycle',
            [
                'group' => 'Content',
                'type' => 'varchar',
                'frontend' => '',
                'label' => 'Billing Cycle',
                'input' => 'text',
                'class' => '',
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => 0,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => 'simple,downloadable,virtual,bundle,configurable',
                'used_for_promo_rules' => true
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
