<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product;

/**
 * Update options button configuration.
 */
class UpdateOptionsButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
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
                                'targetName' => $this->getEditOptionsModal(),
                                'actionName' => 'toggleModal',
                            ],
                            [
                                'targetName' => $this->getTargetName(),
                                'actionName' => 'updateProductOptions',
                            ]
                        ]
                    ]
                ]
            ],
            'on_click' => '',
            'sort_order' => 10
        ];
    }

    /**
     * Return edit options modal name.
     *
     * @return string
     */
    protected function getEditOptionsModal()
    {
        return Product::DATA_SCOPE_SUBSCRIPTION_LISTING . '.'
        . Product::DATA_SCOPE_SUBSCRIPTION_LISTING
        . '.' . Product::DATA_SCOPE_SUBSCRIPTION_PROFILE_PRODUCTS
        . '.editOptionsModal';
    }

    /**
     * Return target name for action.
     *
     * @return string
     */
    protected function getTargetName()
    {
        return Product\Modal\ModifyForm::DATA_SCOPE_MODAL_FORM . '.' . Product\Modal\ModifyForm::DATA_SCOPE_MODAL_FORM;
    }
}
