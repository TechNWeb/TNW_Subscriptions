<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\InvoiceItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\InvoiceItem as Resource;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\InvoiceItem;

/**
 * Invoice item extension attributes collection.
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_idFieldName = 'item_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(InvoiceItem::class, Resource::class);
    }
}
