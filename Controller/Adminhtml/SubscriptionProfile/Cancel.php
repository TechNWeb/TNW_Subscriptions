<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

/**
 * Class Cancel - controller
 */
class Cancel extends SubscriptionProfile
{
    /**
     * Cancel order create
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $this->clearSessionData();
        $resultRedirect->setPath('tnw_subscriptions/subscriptionprofile/index');

        return $resultRedirect;
    }
}
