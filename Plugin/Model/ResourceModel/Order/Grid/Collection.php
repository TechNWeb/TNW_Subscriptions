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
     * Add website id to collection
     *
     * @param OrderGridCollection $collection
     * @return OrderGridCollection
     */
    public function beforeLoad(OrderGridCollection $collection)
    {
        if (!$collection->isLoaded()) {
            $collection->getSelect()->columns(['website_id' => 'store_id']);
        }
        return $collection;
    }
}
