<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Dashboard\Overview;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager;

/**
 * Subscription Overview Message block
 *
 * @method SubscriptionProfile getSubscriptionProfile()
 */
class Message extends Template
{
    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/dashboard/overview/message.phtml';

    /**
     * @var Manager
     */
    private $profileManager;

    /**
     * @param Context $context
     * @param Manager $profileManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Manager $profileManager,
        array $data = []
    ) {
        $this->profileManager = $profileManager;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve message
     *
     * @return string
     */
    public function getMessage()
    {
        $message = '';
        $profile = $this->getSubscriptionProfile();
        if ($profile) {
            $profileOrder = $this->getNextProfileRelation();
            $scheduledAt = null;
            if ($profileOrder) {
                $this->profileManager->setProfileOrderRelation($profileOrder);
                $scheduledAt = $profileOrder->getScheduledAt();
            }
            $message = $this->profileManager->getStatusMessage($profile->getStatus(), $scheduledAt);
        }

        return $message;
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
}
