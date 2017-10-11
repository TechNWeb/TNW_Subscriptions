<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
        $this->_init(SubscriptionProfileOrderInterface::MAIN_TABLE,
            SubscriptionProfileOrderInterface::ID
        );
    }

    /**
     * Retrieve Subscription profile ID by Order ID
     * 
     * @param $orderId
     * @return int|false
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSubscriptionProfileIdByOrder($orderId)
    {
        $select = $this->getConnection()->select();
        $select->from($this->getMainTable(), [SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID])
            ->where(SubscriptionProfileOrderInterface::MAGENTO_ORDER_ID . '=?', $orderId);
        $profileId = $this->getConnection()->fetchOne($select);
        
        if ($profileId) {
            return intval($profileId);
        } else {
            return false;
        }
    }
}
