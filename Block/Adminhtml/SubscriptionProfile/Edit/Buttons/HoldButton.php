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
        if (!$this->canChangeStatus() || $this->isProfileTrial()) {
            return [];
        }

        return [
            'label' => __('Place On Hold'),
            'class' => 'cancel',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'index = change_status',
                                'actionName' => 'toggleModal',
                            ],
                            [
                                'targetName' => 'index = change_status',
                                'actionName' => 'setTitle',
                                'params' => [
                                    __('Are you sure you want to place on hold the profile?')
                                ]
                            ],
                            [
                                'targetName' => 'index = tnw_subscriptionprofile_change_status_popup_form_data_source',
                                'actionName' => 'set',
                                'params' => [
                                    'data.status',
                                    $this->getStatus()
                                ]
                            ],
                        ]
                    ]
                ]
            ],
            'on_click' => '',
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
