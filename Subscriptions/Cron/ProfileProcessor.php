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
use TNW\Subscriptions\Model\SubscriptionProfile\Status\Modifier\PoolInterface;

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
     * Poll of subscription profile status modifiers.
     *
     * @var PoolInterface
     */
    private $statusModifiersPool;

    /**
     * ProfileProcessor constructor.
     * @param Context $context
     * @param Manager $queueManager
     * @param Registry $registry
     * @param PoolInterface $statusModifiersPool
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        Registry $registry,
        PoolInterface $statusModifiersPool
    ) {
        $this->context = $context;
        $this->queueManager = $queueManager;
        $this->registry = $registry;
        $this->statusModifiersPool = $statusModifiersPool;
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
        $allIds = array_keys($itemsCollection->getItems());
        $this->queueManager->makeRunning($allIds);
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
        $this->updateProfilesStatuses($allIds);
    }

    /**
     * Updates profile statuses after queue processing.
     *
     * @param array $allIds
     */
    private function updateProfilesStatuses(array $allIds)
    {
        try {
            foreach ($this->statusModifiersPool->getModifiersInstances() as $modifier) {
                $modifier->modify($allIds);
            }
        } catch (\Exception $e) {
            $this->context->log(__('Error on updating profile statuses - ') . $e->getMessage());
        }
    }
}
