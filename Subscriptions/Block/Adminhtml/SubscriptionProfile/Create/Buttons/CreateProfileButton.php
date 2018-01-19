<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Payment;

class CreateProfileButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        $isModuleEnabled = $this->config->isSubscriptionsActive();
        $button = [
            'label' => __('Create New Subscription Profile'),
            'class' => 'primary',
            'on_click' => sprintf("location.href = '%s';", $this->getCreateProfileUrl()),
            'sort_order' => 20
        ];

        return $isModuleEnabled ? $button : [];
    }

    /**
     * Returns create profile url.
     *
     * @return string
     */
    public function getCreateProfileUrl()
    {
        return $this->getUrl('*/subscriptionprofile_create/start');
    }
}
