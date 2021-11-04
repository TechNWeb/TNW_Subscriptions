<?php
/**
 *  Copyright © 2021 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

/**
 * Used to modify data in Magento\Sales\Model\ResourceModel\Order\Grid\Collection items.
 */
class Collection
{
    /**
     * @param OrderGridCollection $collection
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(OrderGridCollection $collection, $printQuery = false, $logQuery = false)
    {
        if (!$collection->isLoaded()) {
            $collection->getSelect()->columns(['profile_store_id' => 'store_id']);
        }
        return [$printQuery, $logQuery];
    }
}
