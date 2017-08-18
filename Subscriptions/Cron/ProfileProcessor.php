<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron;

use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;

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
     * ProfileProcessor constructor.
     * @param Context $context
     * @param Manager $queueManager
     */
    public function __construct(
        Context $context,
        Manager $queueManager
    ) {
        $this->context = $context;
        $this->queueManager = $queueManager;
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
