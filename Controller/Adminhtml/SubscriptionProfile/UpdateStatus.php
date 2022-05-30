<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use TNW\Subscriptions\Model\EmailNotifierFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\UpdateStatus as UpdateStatusModel;

/**
 * Controller To update status in Subscription Profile
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
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var EmailNotifierFactory
     */
    private $emailNotifierFactory;

    /**
     * @param Context $context
     * @param UpdateStatusModel $updateStatusModel
     * @param JsonFactory $resultJsonFactory
     * @param EmailNotifierFactory $emailNotifierFactory
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        Context $context,
        UpdateStatusModel $updateStatusModel,
        JsonFactory $resultJsonFactory,
        EmailNotifierFactory $emailNotifierFactory,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->updateStatusModel = $updateStatusModel;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->emailNotifierFactory = $emailNotifierFactory;

        parent::__construct($context);
    }

    /**
     * Update status in Subscription Profile.
     *
     * @return Redirect|\Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getPost()->get('subscription_profile_id');
        $newStatus = $this->getRequest()->getParam('status');
        $response = new DataObject();
        $response->setData('result', true);
        $response->setData('ajaxRedirect', $this->_url->getUrl(
            'tnw_subscriptions/subscriptionprofile/edit',
            ['entity_id' => $profileId]
        ));
        try {
            $this->updateStatusModel->updateStatus(
                $profileId,
                $newStatus,
                $this->getRequest()->getParam('suspension_type') == 'billing_cycles'
                    ? $this->getRequest()->getParam('cycles_count')
                    : 0
            );
            $comment = $this->_request->getParam('comment_area') ?: false;
            $notifyCustomer = (bool) $this->_request->getParam('comment_notify') ?: false;
            if ($comment) {
                $this->messageHistoryLogger->log(
                    $comment,
                    $profileId,
                    true,
                    $notifyCustomer
                );
                if ($notifyCustomer) {
                    $this->emailNotifierFactory->create()->addComment($profileId, $comment);
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $response->setData('result', false);
        }

        if ($this->getRequest()->getParam('isAjax', false)) {
            return $this->resultJsonFactory->create()->setJsonData($response->toJson());
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

        return $resultRedirect->setPath(
            'tnw_subscriptions/subscriptionprofile/edit',
            ['entity_id' => $this->getRequest()->getParam('entity_id')]
        );
    }
}
