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
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
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

        $setup->endSetup();
    }
}
