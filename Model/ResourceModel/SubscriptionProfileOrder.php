<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\ResourceModel;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource model for SubscriptionProfileOrder
 */
class SubscriptionProfileOrder extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            SubscriptionProfileOrderInterface::MAIN_TABLE,
            SubscriptionProfileOrderInterface::ID
        );
    }

    /**
     * @param int $orderId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function profileIdsByOrderId($orderId)
    {
        $select = $this->getConnection()->select()
            ->from(['main' => $this->getMainTable()], ['subscription_profile_id'])
            ->where('main.magento_order_id = ?', $orderId);

        return $this->getConnection()->fetchCol($select);
    }
}
