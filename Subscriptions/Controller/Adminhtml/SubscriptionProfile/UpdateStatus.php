<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\UpdateStatus as UpdateStatusModel;

/**
 * Action To update status in Subscription Profile
 */
class UpdateStatus extends Action
{
    /**
     * Model for update status.
     *
     * @var UpdateStatusModel
     */
    private $updateStatusModel;

    /**
     * Message history logger.
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;


    /**
     * @param Context $context
     * @param UpdateStatusModel $updateStatusModel
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        Context $context,
        UpdateStatusModel $updateStatusModel,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->updateStatusModel = $updateStatusModel;
        $this->messageHistoryLogger = $messageHistoryLogger;

        parent::__construct($context);
    }

    /**
     * Update status in Subscription Profile.
     *
     * @return Redirect
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('entity_id');
        $newStatus = $this->getRequest()->getParam('status');

        try {
            $this->updateStatusModel->updateStatus($profileId, $newStatus);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->getRedirect();
    }

    /**
     * Acl check for admin.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'TNW_Subscriptions::SubscriptionProfile_edit'
        );
    }

    /**
     * Retrieve redirect model.
     *
     * @return Redirect
     */
    private function getRedirect()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('entity_id')]);
    }
}
