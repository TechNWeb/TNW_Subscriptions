<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use TNW\Subscriptions\Model\Config\Source\PurchaseType;
use TNW\Subscriptions\Model\Product\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Add filtering in product grid by 'Available For' attr and setup default value for this attr
 *
 * Class AddDefaultValueForPurchaseType
 */
class AddDefaultValueForPurchaseType implements DataPatchInterface
{
    /**
     * @var Collection
     */
    private $productCollection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Action
     */
    private $productAction;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * AddDefaultValueForPurchaseType constructor.
     * @param Collection $productCollection
     * @param StoreManagerInterface $storeManager
     * @param Action $productAction
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param ModuleDataSetupInterface $setup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        Collection $productCollection,
        StoreManagerInterface $storeManager,
        Action $productAction,
        ProductAttributeRepositoryInterface $attributeRepository,
        ModuleDataSetupInterface $setup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->productCollection = $productCollection;
        $this->storeManager = $storeManager;
        $this->productAction = $productAction;
        $this->attributeRepository = $attributeRepository;
        $this->setup = $setup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [InitData::class];
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
     * @throws NoSuchEntityException
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->setup]);
        $id = $this->attributeRepository->get(Attribute::SUBSCRIPTION_PURCHASE_TYPE)->getAttributeId();
        $eavSetup->updateAttribute(Product::ENTITY, $id, 'filterable', 1, null);
        $eavSetup->updateAttribute(Product::ENTITY, $id, 'filterable_in_search', 1, null);
        $eavSetup->updateAttribute(Product::ENTITY, $id, 'is_used_in_grid', 1, null);
        $eavSetup->updateAttribute(Product::ENTITY, $id, 'is_visible_in_grid', 1, null);
        $eavSetup->updateAttribute(Product::ENTITY, $id, 'is_filterable_in_grid', 1, null);
        $eavSetup->updateAttribute(
            Product::ENTITY,
            $id,
            'default_value',
            PurchaseType::ONE_TIME_PURCHASE_TYPE,
            null
        );
    }
}
