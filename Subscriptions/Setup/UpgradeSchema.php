<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        if (version_compare($context->getVersion(), "2.0.1", "<")) {
            $tableName = 'tnw_subscriptions_product_billing_frequency';
            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'sort_order',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'sort_order',
                ]
            );

        }

        if (version_compare($context->getVersion(), "2.0.2", "<")) {
            $tableName = 'tnw_subscriptions_subscription_profile';
            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'engine_code',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => false,
                    'comment' => 'Engine code',
                    'length' => 255
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'shipping_address_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'shipping address id'
                ]
            );

            $setup->getConnection()->addColumn(
                $setup->getTable($tableName),
                'billing_address_id',
                [
                    'type' => Table::TYPE_INTEGER,
                    'unsigned' => true,
                    'nullable' => false,
                    'comment' => 'shipping address id'
                ]
            );
        }

        $setup->endSetup();
    }
}
