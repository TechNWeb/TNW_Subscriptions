<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class CancelButton - adminhtml block for create profile button cancel
 */
class CancelButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Cancel'),
            'class' => 'cancel',
            'on_click' => sprintf("location.href = '%s';", $this->getCancelUrl()),
            'sort_order' => 20
        ];
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('tnw_subscriptions/subscriptionprofile/cancel');
    }
}
