<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\BillingFrequency\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveButton - block to define data for save button on billing frequency
 */
class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Label for save button.
     */
    const LABEL = 'Save Billing Frequency';

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __(self::LABEL),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
