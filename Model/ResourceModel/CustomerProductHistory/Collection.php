<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\CustomerProductHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Subscriptions\Model\CustomerProductHistory;
use TNW\Subscriptions\Model\ResourceModel\CustomerProductHistory as CustomerProductHistoryResourceModel;

/**
 * Class Collection - CustomerProductHistory
 */
class Collection extends AbstractCollection
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            CustomerProductHistory::class,
            CustomerProductHistoryResourceModel::class
        );
    }
}
