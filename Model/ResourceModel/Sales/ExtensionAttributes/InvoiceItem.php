<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \TNW\Subscriptions\Api\Data\InvoiceItemExtensionAttributesInterface;

/**
 * Resource class for invoice item extension attribute.
 */
class InvoiceItem extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            InvoiceItemExtensionAttributesInterface::INVOICE_ITEM_EXTENSION_TABLE,
            InvoiceItemExtensionAttributesInterface::MAGENTO_ITEM_ID
        );
    }
}
