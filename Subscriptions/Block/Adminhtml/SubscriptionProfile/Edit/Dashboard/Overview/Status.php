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
use TNW\Subscriptions\Model\SubscriptionProfileOrder;

/**
 * Subscription Overview Status block
 * 
 * @method SubscriptionProfile getSubscriptionProfile()
 * @method SubscriptionProfileOrder getNextSubscriptionProfileOrder()
 */
class Status extends Template
{
    /**
     * @inheritdoc
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/dashboard/overview/status.phtml';

    /**
     * Status options source
     *
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * @param Context $context
     * @param ProfileStatus $profileStatus
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProfileStatus $profileStatus,
        array $data = []
    ) {
        $this->profileStatus = $profileStatus;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve profile status label
     * 
     * @return string
     */
    public function getStatusLabel()
    {
        $profile = $this->getSubscriptionProfile();
        if ($profile) {
            return $this->profileStatus->getLabelByValue($profile->getStatus());
        } else {
            return null;
        }
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
                    return 'green';
                case ProfileStatus::STATUS_HOLDED:
                case ProfileStatus::STATUS_TRIAL:
                case ProfileStatus::STATUS_PENDING:
                case ProfileStatus::STATUS_COMPLETE:
                    return 'light-green';
                case ProfileStatus::STATUS_SUSPENDED:
                    return 'red';
                case ProfileStatus::STATUS_CANCELED:
                case ProfileStatus::STATUS_PAST_DUE:
                    return 'orange';
            }
        }

        return '';
    }
}
