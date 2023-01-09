<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SalesItemRelation
 */
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
     * @param bool $quoteOnly
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function insertSales(array $data, $quoteOnly = false)
    {
        if (empty($data)) {
            return;
        }

        $columns = [
            'profile_item_id',
            'quote_item_id'
        ];
        if (!$quoteOnly) {
            $columns[] = 'order_item_id';
        }

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

    public function profileIdByQuoteItemId($quoteItemId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(['relation' => $this->getMainTable()], ['quote_item_id', 'profile_item_id'])
            ->joinInner(
                ['profileItem' => $this->getTable('tnw_subscriptions_product_subscription_profile_entity')],
                'relation.profile_item_id = profileItem.entity_id',
                ['subscription_profile_id']
            )
            ->where('relation.quote_item_id = ?', $quoteItemId)
        ;

        return $connection->fetchRow($select);
    }

    public function insertMagentoOrderIdData($profileItemId, $quoteItemId, $orderItemId)
    {
        return $this->getConnection()->update(
            $this->getMainTable(),
            [
                'order_item_id' => (int) $orderItemId
            ],
            [
                'profile_item_id = ?' => (int) $profileItemId,
                'quote_item_id = ?' => (int) $quoteItemId,
            ]
        );

    }
}
