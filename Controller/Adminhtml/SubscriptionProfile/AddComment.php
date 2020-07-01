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
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\EmailNotifierFactory;

/**
 * Add comment for subscription.
 */
class AddComment extends Action
{
    /**
     * Result json factory.
     *
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Data Persistor.
     *
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * Message history logger.
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @var EmailNotifierFactory
     */
    private $emailNotifierFactory;

    /**
     * AddComment constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param DataPersistorInterface $dataPersistor
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param EmailNotifierFactory $emailNotifierFactory
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        DataPersistorInterface $dataPersistor,
        MessageHistoryLogger $messageHistoryLogger,
        EmailNotifierFactory $emailNotifierFactory
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->dataPersistor = $dataPersistor;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->emailNotifierFactory = $emailNotifierFactory;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $response = new DataObject();
        $response->setData('result', true);

        $subscriptionId = $this->dataPersistor->get('subscription_id');

        if ($subscriptionId) {
            $comment = $this->_request->getParam('comment') ?: false;
            $notifyCustomer = (bool) $this->_request->getParam('notify') ?: false;
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
        } else {
            $response->setData('result', false);
        }

        return $this->resultJsonFactory->create()->setJsonData($response->toJson());
    }
}
