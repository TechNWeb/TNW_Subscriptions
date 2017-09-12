<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use Magento\Framework\Stdlib\DateTime;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Modifier to set "Suspended" status
 */
class StatusSuspended extends Base implements ModifierInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getIdsToModify(array $allIds)
    {
        $select = $this->resource->getConnection()->select();
        $select->from(
            ['main_table' => Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE],
            []
        )->join(
            ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
            'main_table.profile_order_id = relation.id',
            [SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID]
        )->join(
            ['profile' => SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY],
            'relation.subscription_profile_id = profile.entity_id',
            []
        )->where(
            'profile.status NOT IN (?)',  $this->getIgnoredStatuses()
        )->where(
            'relation.magento_order_id IS NULL'
        )->where(
            "relation.scheduled_at <= '{$this->getSuspendDate()}' 
            OR main_table.attempt_count >= '{$this->config->getAttemptCount()}'"
        )->where(
            'profile.entity_id IN (?)', $allIds
        )->group(
            SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID
        );

        return $this->resource->getConnection()->fetchCol($select);
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewStatus()
    {
        return ProfileStatus::STATUS_SUSPENDED;
    }


    /**
     * Returns formatted profile suspend date.
     *
     * @return null|string
     */
    private function getSuspendDate()
    {
        $date = new \DateTime();
        $condition = 'P' . $this->config->getGracePeriod() . 'D';
        $date->sub(new \DateInterval($condition));
        return $date->format(DateTime::DATETIME_PHP_FORMAT);
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