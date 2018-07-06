<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;

class NewCustomerButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Create a Customer'),
            'on_click' => sprintf("location.href = '%s';", $this->getNewCustomerUrl()),
            'class' => 'primary',
            'sort_order' => 10
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getNewCustomerUrl()
    {
        $step = $this->stepPool->getNextStep();

        $url = '*/*/';
        $params = [];

        if ($step){
            $url = 'tnw_subscriptions/subscriptionprofile_create/process';
            $params = [
                StepPool::STEP_PARAM_NAME => $step,
                'create_new_customer' => true
            ];
        }

        return $this->getUrl($url, $params);
    }
}
