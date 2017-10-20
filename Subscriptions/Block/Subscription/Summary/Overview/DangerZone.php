<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary\Overview;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Block\Subscription\Summary\Overview;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;

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
     * Provide current website id for config.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Help retrieve current subscription profile model.
     *
     * @var Registry
     */
    private $registry;

    /**
     * DangerZone constructor.
     *
     * @param Template\Context $context
     * @param Config $config
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        Registry $registry,
        array $data = []
    ) {
        $this->config = $config;
        $this->storeManager = $context->getStoreManager();
        $this->registry = $registry;
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
                'entity_id' => $this->registry->registry('tnw_subscription_profile')->getId(),
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
                'entity_id' => $this->registry->registry('tnw_subscription_profile')->getId(),
                'status' => ProfileStatus::STATUS_CANCELED,
                'redirect' => Overview::REDIRECT
            ]
        );
    }

    /**
     * Check whether show "Place On Hold" button or not for current website.
     *
     * @return bool
     */
    public function isPlaceOnHoldActive()
    {
        /** @var SubscriptionProfile $subscriptionProfile */
        $subscriptionProfile = $this->registry->registry('tnw_subscription_profile');
        $status = $subscriptionProfile->getStatus();

        return $this->config->getCanHoldProfile($this->storeManager->getWebsite()->getId()) &&
            (int) $status !== ProfileStatus::STATUS_HOLDED;
    }

    /**
     * Check whether show "Cancel Subscription" button or not for current website.
     *
     * @return bool
     */
    public function isCancelActive()
    {
        return $this->config->getCanCancelProfile($this->storeManager->getWebsite()->getId());
    }
}
