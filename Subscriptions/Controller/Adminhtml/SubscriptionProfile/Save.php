<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

class Save extends SubscriptionProfile
{
    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        //process additional payment data
        $paymentPostData = $this->getRequest()->getParam('payment', []);
        $additionalData = [];
        foreach ($paymentPostData as  $code => $methodData) {
            if ($methodData['method']) {
                $additionalData = isset($methodData['additional']) ? $methodData['additional'] : [];
                $additionalData['method'] = $code;
                break;
            }
        }

        $this->getSubCreateModel()->setPaymentData($additionalData);
        $profiles = $this->getSubCreateModel()->createSubscriptions();
        $this->_getSession()->clearStorage();

        if ($profiles) {
            $this->messageManager->addSuccessMessage(
                sprintf(__('Total of %s profiles was created.'), count($profiles))
            );
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_save'
        );
    }
}
