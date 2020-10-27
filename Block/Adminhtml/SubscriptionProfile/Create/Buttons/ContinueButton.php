<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ContinueButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Continue >>'),
            'class' => 'continue primary',
            'on_click' => '"return false;"',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'index = tnw_subscriptionprofile_create_payment_form',
                                'actionName' => 'beforeSubmit',
                            ],
                        ]
                    ]
                ],
            ],
            'sort_order' => 20
        ];
    }
}
