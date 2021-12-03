<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel\BillingFrequency;

/**
 * Class Collection - BillingFrequency
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\BillingFrequency::class,
            \TNW\Subscriptions\Model\ResourceModel\BillingFrequency::class
        );
        $this->setFlag('admin_gws_filtered', true);
    }
}
