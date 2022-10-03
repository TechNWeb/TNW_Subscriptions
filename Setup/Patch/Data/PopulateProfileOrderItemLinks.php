<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class UpgradeProfileProductCustomOptions - data patch
 */
class PopulateProfileOrderItemLinks implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ModuleDataSetupInterface $setup
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        SerializerInterface $serializer
    ) {
        $this->setup = $setup;
        $this->serializer = $serializer;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
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
     * @throws \Exception
     */
    public function apply()
    {
        $this->setup->startSetup();

        $subscriptionOrderTable = $this->setup->getTable('tnw_subscriptions_subscription_profile_order');
        $connection = $this->setup->getConnection();

        $select = $connection->select()
            ->from(['profileOrder' => $subscriptionOrderTable], ['subscription_profile_id', 'magento_order_id'])
            ->joinInner(
                ['orderItem' => $this->setup->getTable('sales_order_item')],
                'profileOrder.magento_order_id = orderItem.order_id',
                ['item_id', 'product_options']
            )
            ->joinInner(
                ['profileProductItem' => $this->setup
                    ->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                'profileOrder.subscription_profile_id = profileProductItem.subscription_profile_id
                AND profileProductItem.parent_id IS NULL
                ',
                ['profile_item_id' => 'entity_id']
            )
            ->joinLeft(
                ['profileSalesItemLink' => $this->setup
                    ->getTable('tnw_subscriptions_profile_item_sales_item')],
                'profileSalesItemLink.profile_item_id = profile_item_id
                AND item_id = profileSalesItemLink.order_item_id
                ',
                ['profile_item_exists' => 'profile_item_id']
            )
            ->where('magento_order_id IS NOT NULL');
        $profileOrders = $connection->fetchAll($select);
        $linksToPopulate = [];
        foreach ($profileOrders as $profileOrderLinkData) {
            if (!$profileOrderLinkData['profile_item_exists']) {
                $productOptions = json_decode($profileOrderLinkData['product_options'], true);
                if (isset($productOptions['info_buyRequest']['subscribe_active'])
                    && $productOptions['info_buyRequest']['subscribe_active']
                ) {
                    $linksToPopulate[] = [
                        'profile_item_id' => $profileOrderLinkData['profile_item_id'],
                        'order_item_id' => $profileOrderLinkData['item_id']
                    ];
                }
            }
        }
        if ($linksToPopulate) {
            $this->setup->getConnection()->insertArray(
                $this->setup->getTable('tnw_subscriptions_profile_item_sales_item'),
                ['profile_item_id', 'order_item_id'],
                $linksToPopulate
            );
        }
        $this->setup->endSetup();
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '2.3.77';
    }
}
