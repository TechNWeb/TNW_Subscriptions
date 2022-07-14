<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Class UpdateDonationProductAttributes - data patch
 */
class UpdateDonationProductAttributes implements DataPatchInterface, PatchVersionInterface
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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $setup
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $setup,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->setup = $setup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [RemoveRequiredFlagFromProductAttributes::class];
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
                    Product::ENTITY,
                    $attribute->getAttributeId(),
                    'apply_to',
                    implode(',', $applyTo)
                );
            }
        }

        $this->setup->endSetup();
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '2.1.0';
    }
}
