<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\CreditmemoItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\CreditmemoItem as Resource;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\CreditmemoItem;

/**
 * Credit memo item extension attributes collection.
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
        $this->_init(CreditmemoItem::class, Resource::class);
    }
}
