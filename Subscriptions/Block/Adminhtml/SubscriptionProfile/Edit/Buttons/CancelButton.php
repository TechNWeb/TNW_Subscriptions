<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

class CancelButton extends ChangeStatusButton implements ButtonProviderInterface
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
            'label' => __('Cancel'),
            'class' => 'cancel red-text',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to cancel the profile?'
                ) . '\', \'' . $this->getUpdateUrl() . '\')',
            'sort_order' => 20,
        ];
    }

    /**
     * {inheritdoc}
     */
    protected function getStatus()
    {
        return ProfileStatus::STATUS_CANCELED;
    }
}
