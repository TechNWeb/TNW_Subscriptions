<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\Message;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class View - action controller
 */
class View extends \Magento\Backend\App\Action
{
    /**
     * Dispatch request
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu('TNW_Subscription::message');
        $resultPage->getConfig()->getTitle()->prepend(__('Subscription log message'));

        return $resultPage;
    }

    /**
     * Acl check for admin
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::tools_message_view'
        );
    }
}
