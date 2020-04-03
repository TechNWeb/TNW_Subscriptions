<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
                ['sales_order' => $this->getTable('sales_order')],
                'sales_order.entity_id = profile_order.' . SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID,
                []
            )->where('profile_order.' . SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID . ' in (?)', $ids)
            ->where('sales_order.status <> ?', \Magento\Sales\Model\Order::STATE_CANCELED)
            ->group(SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID);

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * Return sum of total price and shipping subscription profile orders for subscriptions
     *
     * @param array $ids
     * @return array
     */
    public function getQuoteItemsData(array $ids)
    {
        $arrItems = [];
        array_filter($ids, function($v, $k) use(&$arrItems) {
            array_push($arrItems, $v['entity_id']);
        }, ARRAY_FILTER_USE_BOTH);

        $select = $this->getConnection()->select()
          ->from(
              ['profile_order' => $this->getTable('tnw_subscriptions_product_subscription_profile_entity')],
              ['entity_id', 'subscription_profile_id']
          )->join(
              ['profile_items' => $this->getTable('tnw_subscriptions_profile_item_sales_item')],
              'profile_order.entity_id = profile_items.profile_item_id',
              'quote_item_id'
            )->join(
                'quote_item',
                'quote_item.item_id = profile_items.quote_item_id',
                '*'
            )->join(
                ['profile_entity' => $this->getTable('tnw_subscriptions_subscription_profile_entity')],
                'profile_entity.entity_id = profile_order.subscription_profile_id',
                'shipping'
            )->where('profile_order.subscription_profile_id in (?)', $arrItems)
            ->group('subscription_profile_id');

        $result = [];
        array_filter($this->getConnection()->fetchAll($select), function($v, $k) use(&$result) {
            $result[$v['subscription_profile_id']] = $v['row_total'] + $v['shipping'];
        }, ARRAY_FILTER_USE_BOTH);

        return $result;
    }
}
