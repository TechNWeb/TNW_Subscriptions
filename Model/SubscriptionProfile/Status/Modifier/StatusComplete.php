<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
        $result = $this->getIdsThatRunThroughAllCycles($allIds);

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
            'profile.term = ?',
            0
        )->where(
            'profile.status NOT IN (?)',
            $this->getIgnoredStatuses()
        )->where(
            'profile.entity_id IN (?)',
            $allIds
        )->where(
            // We take in account items which order is created or quote was deleted (ex. in "hold" status)
            'orders.magento_order_id IS NOT NULL OR orders.magento_quote_id IS NULL'
        )->group(
            ['orders.subscription_profile_id']
        )->having(
            'profile.total_billing_cycles = 0'
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
            ProfileStatus::STATUS_CANCELED,
            ProfileStatus::STATUS_SUSPENDED
        ];
    }
}
