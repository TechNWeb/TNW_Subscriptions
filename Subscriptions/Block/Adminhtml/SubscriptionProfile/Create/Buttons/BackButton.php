<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Adminhtml\SubscriptionProfile\Create\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;

class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        $prevStep = $this->stepPool->getPrevStep();

        $url = '*/*/';
        $params = [];

        if ($prevStep && $prevStep){
            $url = 'tnw_subscriptions/subscriptionprofile/create';
            $params = [
                StepPool::STEP_PARAM_NAME => $prevStep
            ];
        }

        return $this->getUrl($url, $params);
    }
}
