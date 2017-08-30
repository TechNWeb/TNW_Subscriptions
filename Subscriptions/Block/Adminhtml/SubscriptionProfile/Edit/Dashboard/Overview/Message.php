<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Dashboard\Overview;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder;

/**
 * Subscription Overview Message block
 *
 * @method SubscriptionProfile getSubscriptionProfile()
 * @method SubscriptionProfileOrder getNextSubscriptionProfileOrder()
 */
class Message extends Template
{
    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/dashboard/overview/message.phtml';

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve message
     *
     * @return string
     */
    public function getMessage()
    {
        $profile = $this->getSubscriptionProfile();
        if ($profile) {
            switch ($profile->getStatus()) {
                case ProfileStatus::STATUS_ACTIVE:
                    return __('Subscription is current');
                case ProfileStatus::STATUS_HOLDED:
                    return __('Subscription is inactive');
                case ProfileStatus::STATUS_TRIAL:
                    return __('In trial period');
                case ProfileStatus::STATUS_PENDING:
                    return __('Awaiting payment');
                case ProfileStatus::STATUS_COMPLETE:
                    return __('Subscription successfully completed');
                case ProfileStatus::STATUS_SUSPENDED:
                    $days = $this->getDaysPastDue();
                    return __('%1 day%2 past due!', $days, $days !== 1 ? 's' : '');
                case ProfileStatus::STATUS_CANCELED:
                    return __('Subscription is canceled');
                case ProfileStatus::STATUS_PAST_DUE:
                    $days = $this->getDaysUntilSuspended();
                    return __(
                        '%1 day%2 until suspended',
                        $days,
                        $days !== 1 ? 's' : ''
                    );
            }
        }

        return '';
    }

    /**
     * Retrieve status class for appearance
     *
     * @return string
     */
    public function getStatusClass()
    {
        $profile = $this->getSubscriptionProfile();
        if ($profile) {
            switch ($profile->getStatus()) {
                case ProfileStatus::STATUS_ACTIVE:
                case ProfileStatus::STATUS_HOLDED:
                case ProfileStatus::STATUS_TRIAL:
                case ProfileStatus::STATUS_PENDING:
                case ProfileStatus::STATUS_COMPLETE:
                    return 'light-green';
                case ProfileStatus::STATUS_SUSPENDED:
                case ProfileStatus::STATUS_CANCELED:
                case ProfileStatus::STATUS_PAST_DUE:
                    return 'orange';
            }
        }

        return '';
    }

    /**
     * Retrieve the number of days from the last successful payment.
     *
     * @return int
     */
    private function getDaysPastDue()
    {
        $profileOrder = $this->getNextSubscriptionProfileOrder();
        if (!$profileOrder) {
            return 0;
        }
        
        $dateFrom = new \DateTime($profileOrder->getScheduledAt());
        $dateTo = new \DateTime();
        if ($dateFrom > $dateTo) {
            return 0;
        }
        
        return $dateFrom->diff($dateTo)->days;
    }

    /**
     * Retrieve count of days from when the payment was due until today.
     *
     * @return int
     */
    private function getDaysUntilSuspended()
    {
        $profileOrder = $this->getNextSubscriptionProfileOrder();
        if (!$profileOrder) {
            return 0;
        }

        $period = min([
            intval($this->config->getGracePeriod()),
            intval($this->config->getAttemptCount()) * intval($this->config->getAttemptInterval()),
        ]);
        $beginPeriod = new \DateTime($profileOrder->getScheduledAt() . " +$period days");
        $dateNow = new \DateTime();
        if ($beginPeriod > $dateNow ) {
            return 0;
        }

        return $beginPeriod->diff(new \DateTime())->days;
    }
}
