<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class of change status to "Active" button block on Subscription Profile edit form
 */
class ActivateButton extends ChangeStatusButton implements ButtonProviderInterface
{
    /**
     * Retrieve button-specified settings
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->canChangeStatus()) {
            return [];
        }
        
        return [
            'label' => __('Activate'),
            'class' => 'save primary',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to activate the profile?'
                ) . '\', \'' . $this->getUpdateUrl() . '\')',
            'sort_order' => 50,
        ];
    }

    /**
     * {inheritdoc}
     */
    protected function getStatus()
    {
        return ProfileStatus::STATUS_ACTIVE;
    }
}
