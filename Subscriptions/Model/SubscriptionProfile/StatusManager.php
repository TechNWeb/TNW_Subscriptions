<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * The Manager that define logic of status change on Subscription Profile
 */
class StatusManager
{
    /**
     * Get allowed next statuses for Subscription Profile instance
     *
     * @param int $fromStatus
     * @return array
     */
    public function getAllowedStatuses($fromStatus)
    {
        switch ($fromStatus) {
            case ProfileStatus::STATUS_ACTIVE:
            case ProfileStatus::STATUS_PAST_DUE:
            case ProfileStatus::STATUS_TRIAL:
                return [
                    ProfileStatus::STATUS_HOLDED,
                    ProfileStatus::STATUS_CANCELED,
                    ProfileStatus::STATUS_SUSPENDED,
                ];
            case ProfileStatus::STATUS_SUSPENDED:
                return [
                    ProfileStatus::STATUS_HOLDED,
                    ProfileStatus::STATUS_CANCELED,
                    ProfileStatus::STATUS_ACTIVE,
                ];
            case ProfileStatus::STATUS_HOLDED:
                return [
                    ProfileStatus::STATUS_CANCELED,
                    ProfileStatus::STATUS_ACTIVE,
                ];
            case ProfileStatus::STATUS_PENDING:
                return [
                    ProfileStatus::STATUS_CANCELED,
                ];
            default:
                return [];
        }
    }

    /**
     * Retrieve can you change status in Subscription Profile instance
     *
     * @param SubscriptionProfile $profile
     * @param int $nextStatus
     * @return bool
     */
    public function canChangeStatus(SubscriptionProfile $profile, $nextStatus)
    {
        if (!$profile || !$profile->getId()) {
            return false;
        }
        $allowedStatuses = $this->getAllowedStatuses($profile->getStatus());

        return in_array($nextStatus, $allowedStatuses);
    }
}
