<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class CreateButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Create'),
            'class' => 'save primary',
            'on_click' => sprintf("location.href = '%s';", $this->getCreateUrl()),
            'sort_order' => 20
        ];
    }

    /**
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('tnw_subscriptions/subscriptionprofile/save');
    }
}
