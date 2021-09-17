<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency;

/**
 * Class Collection - ProductBillingFrequency ResourceModel
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
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
            \TNW\Subscriptions\Model\ProductBillingFrequency::class,
            \TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency::class
        );
    }
}
