<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

/**
 * Controller for creating subscription profiles.
 */
class Save extends AbstractSave
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $errors = $this->getSaveProcessor()->processSave(
            $this->getRequest()->getParams()
        );
        if (empty($errors)) {
            $this->_getSession()->clearStorage();
            $this->messageManager->addSuccessMessage(
                __('Profile(s) was successfully created.')
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
