<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron;

use Magento\Framework\Registry;
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
     * Registry.
     *
     * @var Registry
     */
    private $registry;

    /**
     * @param Context $context
     * @param Manager $queueManager
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        Registry $registry
    ) {
        $this->context = $context;
        $this->queueManager = $queueManager;
        $this->registry = $registry;
    }

    /**
     * Processes profile queue.
     *
     * @param int $websiteId
     * @throws \RuntimeException.
     */
    public function process($websiteId)
    {
        $successIds = [];
        $itemsCollection = $this->queueManager->getActiveList($websiteId);
        $this->queueManager->makeRunning(array_keys($itemsCollection->getItems()));
        $this->registry->register('profile_process_type', MessageHistoryLogger::PROCESS_TYPE_AUTOMATED);
        foreach ($itemsCollection as $item) {
            try {
                $this->queueManager->processItem($item);
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
}
