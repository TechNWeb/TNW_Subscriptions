<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Create;

use Magento\Backend\Model\View\Result\Redirect;
use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

/**
 * Class Start - controller
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
        $twoFAActive = $this->_getSession()->getData('2fa_passed');
        $this->clearSessionData();
        if ($twoFAActive) {
            $this->_getSession()->setData('2fa_passed', $twoFAActive);
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('tnw_subscriptions/subscriptionprofile/create');
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_create'
        );
    }
}
