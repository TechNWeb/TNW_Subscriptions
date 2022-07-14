<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Class AddNextPaymentAttributes - data patch
 */
class AddNextPaymentAttributes implements DataPatchInterface, PatchVersionInterface
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
        return [UpgradeProfileProductCustomOptions::class];
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

        $this->setup->endSetup();
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '2.1.18';
    }
}
