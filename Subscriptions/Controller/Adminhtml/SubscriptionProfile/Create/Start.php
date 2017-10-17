<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;

use Magento\Backend\Model\View\Result\Redirect;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

/**
 * Class Start
 */
class Start extends SubscriptionProfile
{
    /**
     * Start order create action
     *
     * @return Redirect
     */
    public function execute()
    {
        $this->clearSessionData();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('tnw_subscriptions/subscriptionprofile/create');
    }
}
