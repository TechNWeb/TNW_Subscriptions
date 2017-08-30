<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Adminhtml\SubscriptionProfile\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use TNW\Subscriptions\Api\SubscriptionProfileQueueRepositoryInterface;
use Magento\Framework\Controller\Result\Redirect;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;

/**
 * Class Process
 */
class Process extends Action
{
    /**
     * Repository for retrieving queue items.
     *
     * @var SubscriptionProfileQueueRepositoryInterface
     */
    private $queueRepository;

    /**
     * Profile process queue manager.
     *
     * @var Manager
     */
    private $queueManager;

    /**
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @param Context $context
     * @param SubscriptionProfileQueueRepositoryInterface $queueRepository
     * @param Manager $queueManager
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        Context $context,
        SubscriptionProfileQueueRepositoryInterface $queueRepository,
        Manager $queueManager,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->queueRepository = $queueRepository;
        $this->queueManager = $queueManager;
        $this->messageHistoryLogger = $messageHistoryLogger;

        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        /** @var int $queueId */
        $queueId = (int)$this->getRequest()->getParam('id', 0);

        if ($queueId) {
            try {
                $collection = $this->queueManager->getActiveList();
                $collection->addFieldToFilter('main_table.' . Queue::ID, $queueId);
                /** @var Queue $item */
                $item = $collection->getFirstItem();
                if ($item && $item->getId()){
                    $this->queueManager->makeRunning($queueId);
                    try {
                        $this->queueManager->processItem($item);

                        $this->logProcessItem($item);
                        $successIds[] = $item->getId();
                        $this->queueManager->makeCompleted($successIds);
                        $this->queueManager->updateProfilesStatuses();
                    } catch (\Exception $e) {
                        $this->queueManager->makeError($item->getId(), $e->getMessage());
                    }
                    $this->messageManager->addSuccessMessage(
                        'Record was successfully processed.',
                        'backend'
                    );
                }else {
                    $this->messageManager->addError('Record can not be processed.', 'backend');
                }

            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage(), 'backend');
            }
        }

        return $this->resultRedirectFactory
            ->create()
            ->setPath($this->_redirect->getRefererUrl());
    }

    /**
     * Log Message order create from quote.
     *
     * @param Queue $item
     */
    private function logProcessItem(Queue $item)
    {
        $message = sprintf(
            $this->messageHistoryLogger->getMessage(MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE),
            $this->messageHistoryLogger->getOrderIncrementIdById($item->getProfileOrderId()),
            $this->messageHistoryLogger->getConvertedQuoteId($item->getMagentoQuoteId())
        );

        $this->messageHistoryLogger->log(
            $message,
            $item->getSubscriptionProfileId()
        );
    }

}
