<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SalesItemRelation extends AbstractDb
{
    /**
     * Flag that notifies whether Primary key of table is auto-incremeted
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tnw_subscriptions_profile_item_sales_item', 'profile_item_id');
    }

    /**
     * @param array $data
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertSales(array $data)
    {
        if (empty($data)) {
            return;
        }

        $columns = [
            'profile_item_id',
            'quote_item_id',
            'order_item_id'
        ];

        $data = array_map(function ($data) use ($columns) {
            return array_intersect_key($data, array_flip($columns));
        }, $data);

        $this->getConnection()
            ->insertArray($this->getMainTable(), $columns, $data);
    }

    /**
     * @param int $orderItemId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function profileIdsByOrderItemId($orderItemId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(['relation' => $this->getMainTable()], [])
            ->joinInner(
                ['profileItem' => $this->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                'relation.profile_item_id = profileItem.entity_id',
                ['subscription_profile_id']
            )
            ->where('relation.order_item_id = ?', $orderItemId)
        ;

        return $connection->fetchCol($select);
    }
}
