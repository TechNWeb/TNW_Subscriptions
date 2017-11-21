<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Modifier to set "Complete" status
 */
class StatusComplete extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function getIdsToModify(array $allIds)
    {
        $finished = $this->getIdsThatRunThroughAllCycles($allIds);
        $onHold = $this->getIdsThatHeldAndRunThroughAllCycles($allIds);
        $result = array_unique(array_merge($finished, $onHold));

        return $result;
    }

    /**
     * Get all profile IDs that finished all their cycles and therefore need to be completed.
     *
     * @param array $allIds
     * @return array
     */
    private function getIdsThatRunThroughAllCycles(array $allIds)
    {
        $select = $this->resource->getConnection()->select();
        $select->from(
            ['orders' => $this->resource->getTableName(SubscriptionProfileOrderInterface::MAIN_TABLE)],
            []
        )->join(
            ['profile' => $this->resource->getTableName(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY)],
            'orders.subscription_profile_id = profile.entity_id',
            [
                SubscriptionProfile::ID,
                SubscriptionProfile::TOTAL_BILLING_CYCLES,
                'trial_cycle_count' => new \Zend_Db_Expr('IF(profile.trial_start_date, 1, 0)')
            ]
        )->where(
            'profile.term = ?', 0
        )->where(
            'profile.status NOT IN (?)', $this->getIgnoredStatuses()
        )->where(
            'profile.entity_id IN (?)', $allIds
        )->where(
            'orders.magento_order_id IS NOT NULL'
        )->group(
            ['orders.subscription_profile_id']
        )->having(
            'COUNT(orders.subscription_profile_id) = profile.total_billing_cycles + trial_cycle_count'
        );

        return $this->resource->getConnection()->fetchCol($select);
    }

    /**
     * Get all profile IDs that held and last cycle quote is expired.
     *
     * @param array $allIds
     * @return array
     */
    private function getIdsThatHeldAndRunThroughAllCycles(array $allIds)
    {
        $select = $this->resource->getConnection()->select();
        $select->from(
            ['orders' => $this->resource->getTableName(SubscriptionProfileOrderInterface::MAIN_TABLE)],
            []
        )->join(
            ['profile' => $this->resource->getTableName(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY)],
            'orders.subscription_profile_id = profile.entity_id',
            [SubscriptionProfile::ID]
        )->where(
            'profile.term = ?', 0
        )->where(
            'profile.status = ?', ProfileStatus::STATUS_HOLDED
        )->where(
            'profile.entity_id IN (?)', $allIds
        )->group(
            ['orders.subscription_profile_id']
        )->having(
            'max(orders.scheduled_at) < ?', $this->resource->getConnection()->formatDate(new \DateTime())
        );

        return $this->resource->getConnection()->fetchCol($select);
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewStatus()
    {
        return ProfileStatus::STATUS_COMPLETE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIgnoredStatuses()
    {
        return [
            ProfileStatus::STATUS_COMPLETE,
            ProfileStatus::STATUS_HOLDED,
            ProfileStatus::STATUS_CANCELED,
            ProfileStatus::STATUS_SUSPENDED
        ];
    }
}