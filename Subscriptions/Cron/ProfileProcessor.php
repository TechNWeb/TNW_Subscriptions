<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron;

use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;

/**
 * Class ProfileProcessor
 */
class ProfileProcessor
{
    /**
     * Subscriptions context.
     *
     * @var Context
     */
    private $context;

    /**
     * Profile process queue manager.
     *
     * @var Manager
     */
    private $queueManager;

    /**
     * Message history logger.
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @param Context $context
     * @param Manager $queueManager
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->context = $context;
        $this->queueManager = $queueManager;
        $this->messageHistoryLogger = $messageHistoryLogger;
    }

    /**
     * Processes profile queue.
     *
     * @param int $websiteId
     */
    public function process($websiteId)
    {
        $successIds = [];
        $itemsCollection = $this->queueManager->getActiveList($websiteId);
        $this->queueManager->makeRunning($itemsCollection->getAllIds());
        foreach ($itemsCollection as $item) {
            try {
                $this->queueManager->processItem($item);

                $this->logToMessageHistory($item);

                $successIds[] = $item->getId();
            } catch (\Exception $e) {
                $this->context->log(
                    'Error on processing profile: ' . $e->getMessage()
                );
                $this->queueManager->makeError($item->getId(), $e->getMessage());
            }
        }
        $this->queueManager->makeCompleted($successIds);
        $this->queueManager->updateProfilesStatuses();
    }

    /**
     * Log message for Subscription Profile message history.
     *
     * @param \TNW\Subscriptions\Model\Queue $item
     *
     * @return void
     */
    private function logToMessageHistory(\TNW\Subscriptions\Model\Queue $item)
    {
        $message = sprintf(
            $this->messageHistoryLogger->getMessage(MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE),
            $this->messageHistoryLogger->getOrderIncrementIdById($item->getProfileOrderId()),
            $this->messageHistoryLogger->getConvertedQuoteId($item->getMagentoQuoteId())
        );

        $this->messageHistoryLogger->log(
            $message,
            $item->getSubscriptionProfileId(),
            false,
            false,
            true
        );
    }
}
