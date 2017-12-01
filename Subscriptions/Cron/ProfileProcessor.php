<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Cron;

use Magento\Framework\Registry;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\PoolInterface;

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
    private $statusProcessorsPool;

    /**
     * @param Context $context
     * @param Manager $queueManager
     * @param Registry $registry
     * @param PoolInterface $statusProcessorsPool
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        Registry $registry,
        PoolInterface $statusProcessorsPool
    ) {
        $this->context = $context;
        $this->queueManager = $queueManager;
        $this->registry = $registry;
        $this->statusProcessorsPool = $statusProcessorsPool;
    }

    /**
     * Processes profile queue.
     *
     * @param int $websiteId
     * @throws \RuntimeException.
     */
    public function process($websiteId)
    {
        $profileIds = [];
        $successIds = [];
        $itemsCollection = $this->queueManager->getActiveList($websiteId);
        $allIds = array_keys($itemsCollection->getItems());
        $this->queueManager->makeRunning($allIds);
        $this->setProcessTypeParam();
        /** @var \TNW\Subscriptions\Model\Queue $item */
        foreach ($itemsCollection as $item) {
            $profileIds[] = $item->getSubscriptionProfileId();
            if ($this->passWithoutProcessing($item)) {
                continue;
            }
            try {
                if ($this->queueManager->processItem($item)) {
                    $successIds[] = $item->getId();
                }
            } catch (\Exception $e) {
                $this->context->log(
                    'Error on processing profile: ' . $e->getMessage()
                );
                $this->queueManager->makeError($item->getId(), $e->getMessage());
            }
        }

        $this->queueManager->makeCompleted($successIds);
        if (!empty($profileIds)){
            $this->updateProfilesStatuses($profileIds);
        }
    }

    /**
     * Updates profile statuses after queue processing.
     *
     * @param array $allIds
     */
    public function updateProfilesStatuses(array $allIds)
    {
        try {
            foreach ($this->statusProcessorsPool->getProcessorsInstances() as $modifier) {
                $modifier->process($allIds);
            }
        } catch (\Exception $e) {
            $this->context->log(__('Error on updating profile statuses - ') . $e->getMessage());
        }
    }

    /**
     * Sets process type param to registry.
     */
    private function setProcessTypeParam()
    {
        $this->registry->unregister('profile_process_type');
        $this->registry->register('profile_process_type', MessageHistoryLogger::PROCESS_TYPE_AUTOMATED);
    }

    /**
     * Check if queue item need to be passed without processing.
     * This items later may be used to change their statuses.
     *
     * @param \TNW\Subscriptions\Model\Queue $item
     * @return bool
     */
    private function passWithoutProcessing(\TNW\Subscriptions\Model\Queue $item)
    {
        $result =
            $item->getData(SubscriptionProfile::CANCEL_BEFORE_NEXT_CYCLE);

        return $result;
    }
}
