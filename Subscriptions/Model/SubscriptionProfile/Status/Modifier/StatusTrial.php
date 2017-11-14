<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;

/**
 * Modifier to set "Trial" status
 */
class StatusTrial extends Base
{
    /**
     * @inheritdoc
     */
    protected function getIdsToModify(array $allIds)
    {
        $currentDate = $this->resource->getConnection()->formatDate((new \DateTime()));
        $select = $this->resource->getConnection()->select();
        $select->from(
            ['main_table' => $this->resource->getTableName(Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE)],
            []
        )->join(
            ['relation' =>  $this->resource->getTableName(SubscriptionProfileOrderInterface::MAIN_TABLE)],
            'main_table.profile_order_id = relation.id',
            [SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID]
        )->join(
            ['profile' => $this->resource->getTableName(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY)],
            'relation.subscription_profile_id = profile.entity_id',
            []
        )->where(
            'profile.status NOT IN (?)', $this->getIgnoredStatuses()
        )->where(
            'main_table.status IN (?)', $this->getQueueStatuses()
        )->where(
            'profile.entity_id IN (?)', $allIds
        )->where(
            'profile.trial_start_date IS NOT NULL'
        )->where(
            'profile.trial_start_date <= ? AND ? <= profile.start_date',
            $currentDate
        )->group(
            ['relation.subscription_profile_id']
        );

        return $this->resource->getConnection()->fetchCol($select);
    }

    /**
     * @inheritdoc
     */
    protected function getNewStatus()
    {
        return ProfileStatus::STATUS_TRIAL;
    }

    /**
     * @inheritdoc
     */
    protected function getIgnoredStatuses()
    {
        return [
            ProfileStatus::STATUS_COMPLETE,
            ProfileStatus::STATUS_HOLDED,
            ProfileStatus::STATUS_CANCELED,
            ProfileStatus::STATUS_SUSPENDED,
            ProfileStatus::STATUS_ACTIVE,
            ProfileStatus::STATUS_TRIAL,
            ProfileStatus::STATUS_PAST_DUE,
        ];
    }

    /**
     * Returns list of queue statuses.
     *
     * @return array
     */
    private function getQueueStatuses()
    {
        return [
            QueueStatus::QUEUE_STATUS_PENDING,
            QueueStatus::QUEUE_STATUS_COMPLETE,
        ];
    }
}
