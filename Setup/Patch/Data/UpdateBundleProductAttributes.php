<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use TNW\Subscriptions\Model\Product\Attribute;

/**
 * Patch updates subscription product attributes to support bundle product type
 */
class UpdateBundleProductAttributes implements DataPatchInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create();

        $attributesToUpdate = [
            Attribute::SUBSCRIPTION_PURCHASE_TYPE,
            Attribute::SUBSCRIPTION_TRIAL_STATUS,
            Attribute::SUBSCRIPTION_TRIAL_LENGTH,
            Attribute::SUBSCRIPTION_TRIAL_LENGTH_UNIT,
            Attribute::SUBSCRIPTION_TRIAL_PRICE,
            Attribute::SUBSCRIPTION_TRIAL_START_DATE,
            Attribute::SUBSCRIPTION_OFFER_FLAT_DISCOUNT,
            Attribute::SUBSCRIPTION_DISCOUNT_TYPE,
            Attribute::SUBSCRIPTION_DISCOUNT_AMOUNT,
            Attribute::SUBSCRIPTION_START_DATE,
            Attribute::SUBSCRIPTION_LOCK_PRODUCT_PRICE,
            Attribute::SUBSCRIPTION_UNLOCK_PRESET_QTY,
            Attribute::SUBSCRIPTION_SAVINGS_CALCULATION,
            Attribute::SUBSCRIPTION_HIDE_QTY,
            Attribute::SUBSCRIPTION_INFINITE_SUBSCRIPTIONS,
            Attribute::SUBSCRIPTION_TRIAL_CAN_SKIP
        ];

        foreach ($attributesToUpdate as $attributeId) {
            $eavSetup->updateAttribute(
                Product::ENTITY,
                $attributeId,
                'apply_to',
                'simple,virtual,downloadable,configurable,bundle'
            );
        }
    }
}
