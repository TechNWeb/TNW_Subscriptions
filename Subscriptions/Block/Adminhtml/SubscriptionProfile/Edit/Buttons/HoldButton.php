<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class of change status to "On Hold" button block on Subscription Profile edit form
 */
class HoldButton extends ChangeStatusButton implements ButtonProviderInterface
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
            'label' => __('Place On Hold'),
            'class' => 'cancel',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to place on hold the profile?'
                ) . '\', \'' . $this->getUpdateUrl() . '\')',
            'sort_order' => 30,
        ];
    }

    /**
     * {inheritdoc}
     */
    protected function getStatus()
    {
        return ProfileStatus::STATUS_HOLDED;
    }
}
