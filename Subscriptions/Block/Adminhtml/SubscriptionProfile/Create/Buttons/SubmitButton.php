<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Form;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

class SubmitButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $configurableModal = Product::DATA_SCOPE_SUBSCRIPTION_LISTING . '.'
            . Product::DATA_SCOPE_SUBSCRIPTION_LISTING
            . '.' . Product::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS
            . '.configurableModal';

        return [
            'label' => __('Ok'),
            'class' => 'primary',
            'data_attribute' => [
                'mage-init' => [
                    'Magento_Ui/js/form/button-adapter' => [
                        'actions' => [
                            [
                                'targetName' => $configurableModal,
                                'actionName' => 'toggleModal',
                            ],
                            [
                                'targetName' => Form::DATA_SCOPE_MODAL_FORM . '.' . Form::DATA_SCOPE_MODAL_FORM,
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
