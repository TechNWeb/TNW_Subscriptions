<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Upgrade schema for TNW Subscriptions.
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "2.0.3", "<")) {
            $this->addInvoiceItemExtensionAttributeTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * Create table 'tnw_subscriptions_subscription_profile_message_history'.
     *
     * @param SchemaSetupInterface $setup
     */
    private function addInvoiceItemExtensionAttributeTable(SchemaSetupInterface $setup)
    {
        if (!$setup->tableExists(SalesExtensionAttributesInterface::INVOICE_ITEM_EXTENSION_TABLE)) {
            $table = $setup->getConnection()->newTable(
                SalesExtensionAttributesInterface::INVOICE_ITEM_EXTENSION_TABLE
            )->addColumn(
                SalesExtensionAttributesInterface::MAGENTO_ITEM_ID,
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Magento quote item ID'
            )->addColumn(
                SalesExtensionAttributesInterface::EXT_ATTRIBUTE_INITIAL_FEE,
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false,
                    'default' => '0.0000'
                ],
                'Initial fee'
            )->addColumn(
                SalesExtensionAttributesInterface::EXT_ATTRIBUTE_BASE_INITIAL_FEE,
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false,
                    'default' => '0.0000'
                ],
                'Base initial fee'
            );

            $setup->getConnection()->createTable($table);
        }
    }
}
