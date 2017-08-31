<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class of change status to "Suspend" button block on Subscription Profile edit form
 */
class SuspendButton extends ChangeStatusButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->canChangeStatus()) {
            return [];
        }

        return [
            'label' => __('Suspend'),
            'class' => 'cancel red-text',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to suspend the profile?'
                ) . '\', \'' . $this->getUpdateUrl() . '\')',
            'sort_order' => 40,
        ];
    }

    /**
     * {inheritdoc}
     */
    protected function getStatus()
    {
        return ProfileStatus::STATUS_SUSPENDED;
    }
}
