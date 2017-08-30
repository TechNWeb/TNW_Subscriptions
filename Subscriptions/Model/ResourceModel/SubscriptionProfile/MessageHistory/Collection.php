<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Subscriptions\Api\Data\SubscriptionProfileMessageHistoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\MessageHistory as ResourceMessageHistory;

class Collection extends AbstractCollection
{
    /**
     * Define resource model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(MessageHistory::class, ResourceMessageHistory::class);
    }

    /**
     * Add user data to select.
     */
    private function addUserDataToSelect()
    {
        $select = $this->getSelect();

        $tableName = $this->getTable('admin_user');
        $select->joinLeft(
            $tableName,
            'main_table.user_id = ' . $tableName . '.user_id',
            ['firstname', 'lastname', 'email']
        );
    }

    /**
     * Get sorted and filtered data for change history in admin panel.
     *
     * @param $parentId
     *
     * @return array
     */
    public function getDataForChangeHistory($parentId)
    {
        $this->addFilter(SubscriptionProfileMessageHistoryInterface::PARENT_ID, $parentId);
        $this->addUserDataToSelect();
        $this->setOrder(SubscriptionProfileMessageHistoryInterface::CREATED_AT);
        $this->setPageSize(50);

        return $this->getData();
    }
}
