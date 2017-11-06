<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;

/**
 * Collection class for Subscription Profile model.
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resources
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \TNW\Subscriptions\Model\SubscriptionProfile::class,
            \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile::class
        );
    }

    /**
     * Return sum of all paid subscription profile orders for subscriptions with entity_id in_array $ids.
     *
     * @param array $ids
     * @return array
     */
    public function getCurrentValues(array $ids)
    {
        $select = $this->getConnection()->select()
            ->from(
                ['profile_order' => $this->getTable(SubscriptionProfileOrderInterface::MAIN_TABLE)],
                [
                    'profile_id' => 'subscription_profile_id',
                    'total' => new \Zend_Db_Expr('sum(sales_order.' . OrderInterface::GRAND_TOTAL . ')'),
                ]
            )->join(
                $this->getTable('sales_order'),
                'sales_order.entity_id = profile_order.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
                []
            )->where('profile_order.' . SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID . ' in (?)', $ids)
            ->where('sales_order.status <> ?', \Magento\Sales\Model\Order::STATE_CANCELED)
            ->group(SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID);

        return $this->getConnection()->fetchPairs($select);
    }
}
