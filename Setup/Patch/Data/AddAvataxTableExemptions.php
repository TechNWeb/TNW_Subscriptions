<?php

namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use TNW\Subscriptions\Api\Data\CreditmemoItemExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\InvoiceItemExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\OrderItemExtensionAttributesInterface;
use TNW\Subscriptions\Api\Data\QuoteItemExtensionAttributesInterface;

class AddAvataxTableExemptions implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
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
     * Add subscription item extension attributes db tables to Avatax exemptions list
     * @return void
     */
    public function apply()
    {
        $default = $this->scopeConfig->getValue('tax/avatax_advanced/avatax_table_exemptions')
            ?? 'negotiable_quote_item,company_order_entity';
        $subscriptionExemptionList = implode(
            ',',
            [
                QuoteItemExtensionAttributesInterface::QUOTE_ITEM_EXTENSION_TABLE,
                OrderItemExtensionAttributesInterface::ORDER_ITEM_EXTENSION_TABLE,
                InvoiceItemExtensionAttributesInterface::INVOICE_ITEM_EXTENSION_TABLE,
                CreditmemoItemExtensionAttributesInterface::CREDITMEMO_ITEM_EXTENSION_TABLE
            ]
        );

        $this->configWriter->save(
            'tax/avatax_advanced/avatax_table_exemptions',
            implode(',', [$default, $subscriptionExemptionList])
        );
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
