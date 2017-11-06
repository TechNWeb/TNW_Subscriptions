<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Overview;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Block\Subscription\Summary\Overview;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;

/**
 * Provide necessary information to render "Danger Zone" section on Subscription Profile Overview page.
 */
class DangerZone extends Template
{
    /**
     * Provide config values for "Place on Hold" and "Cancel".
     *
     * @var Config
     */
    private $config;

    /**
     * Help retrieve current subscription profile model.
     *
     * @var Registry
     */
    private $registry;

    /**
     * Subscription profile status manager.
     *
     * @var StatusManager
     */
    private $statusManager;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param Registry $registry
     * @param StatusManager $statusManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        Registry $registry,
        StatusManager $statusManager,
        array $data = []
    ) {
        $this->config = $config;
        $this->registry = $registry;
        $this->statusManager = $statusManager;
        parent::__construct($context, $data);
    }

    /**
     * Get url for update subscription profile status(hold) with redirect on Overview page.
     *
     * @return string
     */
    public function getPlaceOnHoldUrl()
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription_actions/UpdateStatus',
            [
                'entity_id' => $this->getCurrentSubscriptionProfile()->getId(),
                'status' => ProfileStatus::STATUS_HOLDED,
                'redirect' => Overview::REDIRECT
            ]
        );
    }

    /**
     * Get url for update subscription profile status(cancel) with redirect on Overview page.
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription_actions/UpdateStatus',
            [
                'entity_id' => $this->getCurrentSubscriptionProfile()->getId(),
                'status' => ProfileStatus::STATUS_CANCELED,
                'redirect' => Overview::REDIRECT
            ]
        );
    }

    /**
     * Get url for update subscription profile status(active) with redirect on Overview page.
     *
     * @return string
     */
    public function getReActivateUrl()
    {
        return $this->getUrl(
            'tnw_subscriptions/subscription_actions/UpdateStatus',
            [
                'entity_id' => $this->getCurrentSubscriptionProfile()->getId(),
                'status' => ProfileStatus::STATUS_ACTIVE,
                'redirect' => Overview::REDIRECT
            ]
        );
    }

    /**
     * Check whether show "Place On Hold" button.
     *
     * @return bool
     */
    public function isPlaceOnHoldActive()
    {
        return $this->statusManager->canHoldSubscription($this->getCurrentSubscriptionProfile());
    }

    /**
     * Check whether show "Cancel Subscription" button.
     *
     * @return bool
     */
    public function isCancelActive()
    {
        return $this->statusManager->canCancelSubscription($this->getCurrentSubscriptionProfile());
    }

    /**
     * Check whether show "Re-activate" button.
     *
     * @return bool
     */
    public function isReActiveActive()
    {
        return $this->statusManager->canReActiveSubscription($this->getCurrentSubscriptionProfile());
    }

    /**
     * Return current subscription profile.
     *
     * @return SubscriptionProfile
     */
    private function getCurrentSubscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }
}
