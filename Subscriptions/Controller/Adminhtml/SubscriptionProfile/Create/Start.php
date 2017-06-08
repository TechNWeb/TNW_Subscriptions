<?php

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create as ProfileCreate;;

class Start extends ProfileCreate
{
    /**
     * Start order create action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $this->_getSession()->clearStorage();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('tnw_subscriptions/subscriptionprofile/create');
    }
}
