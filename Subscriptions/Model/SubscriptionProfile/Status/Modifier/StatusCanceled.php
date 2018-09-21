<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier;

use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Modifier to set "Canceled" status.
 */
class StatusCanceled extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function getIdsToModify(array $allIds)
    {
        $select = $this->resource->getConnection()->select();
        $select
            ->from(
                ['profile' => $this->resource->getTableName(SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY)],
                [SubscriptionProfile::ID]
            )->where('profile.' . SubscriptionProfile::CANCEL_BEFORE_NEXT_CYCLE . ' > ?', 0)
            ->where('profile.status NOT IN (?)', $this->getIgnoredStatuses())
            ->where('profile.entity_id IN (?)', $allIds);

        return $this->resource->getConnection()->fetchCol($select);
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewStatus()
    {
        return ProfileStatus::STATUS_CANCELED;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIgnoredStatuses()
    {
        return [
            ProfileStatus::STATUS_CANCELED,
        ];
    }
}
