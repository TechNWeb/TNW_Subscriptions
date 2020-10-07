<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\QuoteItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\QuoteItem;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\QuoteItem as Resource;

/**
 * Class Collection - ExtensionAttributes QuoteItem resource model
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'item_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            QuoteItem::class,
            Resource::class
        );
    }
}
