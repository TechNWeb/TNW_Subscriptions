<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\GenericButton;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Abstract class of change status button block on Subscription Profile edit form
 */
abstract class ChangeStatusButton extends GenericButton
{
    /**
     * Registry model
     *
     * @var Registry
     */
    private $registry;

    /**
     * The Manager that define logic of status change on Subscription Profile
     * 
     * @var StatusManager
     */
    private $statusManager;
    
    /**
     * @param Context $context
     * @param Registry $registry
     * @param StatusManager $statusManager
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StatusManager $statusManager
    ) {
        $this->registry = $registry;
        $this->statusManager = $statusManager;
        parent::__construct($context);
    }

    /**
     * Get value to change status
     * 
     * @return int
     */
    abstract protected function getStatus();

    /**
     * Retrieve action url
     *
     * @return string
     */
    protected function getUpdateUrl()
    {
        return $this->getUrl('*/*/updatestatus', ['entity_id' => $this->getModelId(), 'status' => $this->getStatus()]);
    }

    /**
     * Get current Subscription Profile model
     *
     * @return SubscriptionProfile|null
     */
    protected function getCurrentSubscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }

    /**
     * Can change status
     * 
     * @return bool
     */
    protected function canChangeStatus()
    {
        $profile = $this->getCurrentSubscriptionProfile();
        
        return $this->statusManager->canChangeStatus($profile, $this->getStatus());
    }

    /**
     * Is profile trial
     *
     * @return bool
     */
    protected function isProfileTrial()
    {
        $profile = $this->getCurrentSubscriptionProfile();
        if($profile->getData('status') == ProfileStatus::STATUS_TRIAL) {
            return true;
        }
        return false;
    }
}
