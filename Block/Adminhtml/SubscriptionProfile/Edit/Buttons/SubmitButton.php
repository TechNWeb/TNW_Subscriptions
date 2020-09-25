<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Edit\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SubmitButton - adminhtml block for edit profile button submit
 */
class SubmitButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Ok'),
            'class' => 'primary',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'index = configurableModal',
                                'actionName' => 'toggleModal',
                            ],
                            [
                                'targetName' => 'tnw_subscriptionprofile_summary_add_product_modal_form.'
                                    . 'tnw_subscriptionprofile_summary_add_product_modal_form',
                                'actionName' => 'setConfigurableData',
                            ]
                        ]
                    ]
                ]
            ],
            'on_click' => '',
            'sort_order' => 10
        ];
    }
}
