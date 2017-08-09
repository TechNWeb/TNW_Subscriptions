<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;

class CreateButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Create'),
            'class' => 'primary',
            'on_click' => '',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => 'index = ' . Payment::DATA_SCOPE_PAYMENT_FORM,
                                'actionName' => 'beforeSubmit',
                            ]
                        ]
                    ]
                ]
            ],
            'sort_order' => 20
        ];
    }
}
