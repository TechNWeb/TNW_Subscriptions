<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * The Manager that define logic of status change on Subscription Profile
 */
class StatusManager
{
    /**
     * Subscriptions config.
     *
     * @var Config
     */
    private $config;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Config $config, StoreManagerInterface $storeManager)
    {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Get allowed next statuses for Subscription Profile instance
     *
     * @param int $fromStatus
     * @return array
     */
    public function getAllowedStatuses($fromStatus)
    {
        $result = [];
        switch ($fromStatus) {
            case ProfileStatus::STATUS_ACTIVE:
            case ProfileStatus::STATUS_PAST_DUE:
            case ProfileStatus::STATUS_TRIAL:
            $result = [
                    ProfileStatus::STATUS_HOLDED,
                    ProfileStatus::STATUS_CANCELED,
                    ProfileStatus::STATUS_SUSPENDED,
                ];
            break;
            case ProfileStatus::STATUS_SUSPENDED:
                $result = [
                    ProfileStatus::STATUS_HOLDED,
                    ProfileStatus::STATUS_CANCELED,
                    ProfileStatus::STATUS_ACTIVE,
                ];
                break;
            case ProfileStatus::STATUS_HOLDED:
                $result = [
                    ProfileStatus::STATUS_CANCELED,
                    ProfileStatus::STATUS_ACTIVE,
                ];
                break;
            case ProfileStatus::STATUS_PENDING:
                $result = [
                    ProfileStatus::STATUS_CANCELED,
                ];
                break;
        }

        return $result;
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

    /**
     * Check if we can hold subscription.
     *
     * @param \Magento\Framework\DataObject $subscription
     * @return bool
     */
    public function canHoldSubscription( \Magento\Framework\DataObject $subscription)
    {
        $result = ((int)$subscription->getStatus() === ProfileStatus::STATUS_ACTIVE) ? true : false;

        return $result && $this->config->getCanHoldProfile($this->getWebsiteId());
    }

    /**
     * Check if we can cancel subscription.
     *
     * @param \Magento\Framework\DataObject $subscription
     * @return bool
     */
    public function canCancelSubscription(\Magento\Framework\DataObject $subscription)
    {
        $result = true;
        switch ($subscription->getStatus()) {
            case ProfileStatus::STATUS_CANCELED:
            case ProfileStatus::STATUS_PENDING:
            case ProfileStatus::STATUS_COMPLETE:
            case ProfileStatus::STATUS_PAST_DUE:
                $result = false;
                break;
        }

        return $result && $this->config->getCanCancelProfile($this->getWebsiteId());
    }

    /**
     * Check if we can re-activate subscription.
     *
     * @param \Magento\Framework\DataObject $subscription
     * @return bool
     */
    public function canReActiveSubscription(\Magento\Framework\DataObject $subscription)
    {
        return (int)$subscription->getStatus() === ProfileStatus::STATUS_HOLDED;
    }

    /**
     * Return current website id.
     *
     * @return int
     */
    private function getWebsiteId()
    {
        if (!$this->websiteId) {
            $this->websiteId = $this->storeManager->getWebsite()->getId();
        }

        return $this->websiteId;
    }
}
