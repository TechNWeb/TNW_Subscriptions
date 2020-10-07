<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile;

use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use TNW\Subscriptions\Model\EmailNotifierFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Status\UpdateStatus as UpdateStatusModel;

/**
 * Cancel subscription - controller
 */
class CancelSubscription extends Action
{
    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Model for update status.
     *
     * @var UpdateStatusModel
     */
    private $updateStatusModel;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @var EmailNotifierFactory
     */
    private $emailNotifierFactory;

    /**
     * @param Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param UpdateStatusModel $updateStatusModel
     * @param JsonFactory $resultJsonFactory
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param EmailNotifierFactory $emailNotifierFactory
     */
    public function __construct(
        Action\Context $context,
        DataPersistorInterface $dataPersistor,
        UpdateStatusModel $updateStatusModel,
        JsonFactory $resultJsonFactory,
        MessageHistoryLogger $messageHistoryLogger,
        EmailNotifierFactory $emailNotifierFactory
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->updateStatusModel = $updateStatusModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->emailNotifierFactory = $emailNotifierFactory;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $option = $this->getRequest()->getPost()->get('cancel_button_popup_options');
        $subscriptionId = $this->dataPersistor->get('subscription_id');

        $response = new DataObject();
        $response->setData('result', true);
        $response->setData('ajaxRedirect', $this->_url->getUrl(
            'tnw_subscriptions/subscriptionprofile/edit',
            ['entity_id' => $subscriptionId]
        ));
        try {
            if ($option == 'next') {
                $this->updateStatusModel->updateStatusBeforeNextBillingCycle($subscriptionId);
            } elseif ($option == 'now') {
                $this->updateStatusModel->updateStatus($subscriptionId, ProfileStatus::STATUS_CANCELED);
            }
            $comment = $this->_request->getParam('comment_area') ?: false;
            $notifyCustomer = (bool) $this->_request->getParam('comment_notify') ?: false;
            if ($comment) {
                $this->messageHistoryLogger->log(
                    $comment,
                    $subscriptionId,
                    true,
                    $notifyCustomer
                );
                if ($notifyCustomer) {
                    $this->emailNotifierFactory->create()->addComment($subscriptionId, $comment);
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $response->setData('result', false);
        }

        if ($this->getRequest()->getParam('isAjax', false)) {
            return $this->resultJsonFactory->create()->setJsonData($response->toJson());
        }

        return $this->resultRedirectFactory->create()
            ->setPath('tnw_subscriptions/subscriptionprofile/edit', ['entity_id' => $subscriptionId]);
    }
}
