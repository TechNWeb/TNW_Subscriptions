<?php

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

class Cancel extends \Magento\Sales\Controller\Adminhtml\Order\Create
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

        $this->_getSession()->clearStorage();
        $resultRedirect->setPath('tnw_subscriptions/subscriptionprofile/index');

        return $resultRedirect;
    }
}
