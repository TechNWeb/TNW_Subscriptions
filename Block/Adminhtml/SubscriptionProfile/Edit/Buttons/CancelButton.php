<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;

/**
 * Class of change status to "Cancel" button block on Subscription Profile edit form
 */
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
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'index = cancelModal',
                                'actionName' => 'toggleModal',
                            ],
                            [
                                'targetName' => 'index = cancelModal',
                                'actionName' => 'setTitle',
                                'params' => [
                                    __('Are you sure you want to cancel Subscription %1?',
                                        SubscriptionProfileInterface::LABEL_PREFIX . $this->getModelId())
                                ]
                            ],
                            [
                                'targetName' => 'index = tnw_subscriptionprofile_cancel_button_popup_form_data_source',
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
