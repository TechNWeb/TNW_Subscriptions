<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Used to modify data in Magento\Sales\Model\ResourceModel\Order\Grid\Collection items.
 */
class Collection
{
    /**
     * Add subscription id to order data collection.
     *
     * @param OrderGridCollection $collection
     * @return null
     */
    public function beforeLoad(OrderGridCollection $collection)
    {
        $collection->join(
            ['profile_table' => SubscriptionProfileOrderInterface::MAIN_TABLE],
            'main_table.entity_id = profile_table.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
            []
        );

        $collection->addExpressionFieldToSelect('profiles', 'GROUP_CONCAT(profile_table.{{profileId}})', [
            'profileId' => SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID
        ]);

        $collection->getSelect()->group($collection->getIdFieldName());

        return null;
    }
}
