<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\ReBill;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\ReBill as ResourceModel;
use TNW\Subscriptions\Model\SubscriptionProfile\ReBill as DataModel;

/**
 * Class Collection - resource collection for subscription re-bills
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            DataModel::class,
            ResourceModel::class
        );
    }
}
