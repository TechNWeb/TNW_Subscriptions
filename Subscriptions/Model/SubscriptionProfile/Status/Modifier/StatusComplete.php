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
class StatusComplete extends Base implements ModifierInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getIdsToModify(array $allIds)
    {
        $select = $this->resource->getConnection()->select();
        $select->from(
            ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
            []
        )->join(
            ['profile' => SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY],
            'relation.subscription_profile_id = profile.entity_id',
            [SubscriptionProfile::ID, SubscriptionProfile::TOTAL_BILLING_CYCLES]
        )->where(
            'profile.term = ?', 0
        )->where(
            'profile.status NOT IN (?)', $this->getIgnoredStatuses()
        )->where(
            'profile.entity_id IN (?)', $allIds
        )->where(
            'relation.magento_order_id IS NOT NULL'
        )->group(
            ['relation.subscription_profile_id']
        )->having(
            'COUNT(relation.subscription_profile_id) = profile.total_billing_cycles'
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
            ProfileStatus::STATUS_SUSPENDED,
            ProfileStatus::STATUS_PAST_DUE
        ];
    }
}