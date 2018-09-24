<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Cron;

use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\PoolInterface;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;

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
     * Poll of subscription profile status modifiers.
     *
     * @var PoolInterface
     */
    private $statusProcessorsPool;

    /**
     * @param Context $context
     * @param Manager $queueManager
     * @param PoolInterface $statusProcessorsPool
     */
    public function __construct(
        Context $context,
        Manager $queueManager,
        PoolInterface $statusProcessorsPool
    ) {
        $this->context = $context;
        $this->queueManager = $queueManager;
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

        $canceledCollection = $this->queueManager->getBaseCollection()
            ->addFieldToFilter('profile.status', ProfileStatus::STATUS_CANCELED)
            ->addFieldToFilter('main_table.status', QueueStatus::QUEUE_STATUS_PENDING);

        $this->queueManager->makeDelete($canceledCollection->getAllIds());

        /** @var \TNW\Subscriptions\Model\Queue $queue */
        foreach ($this->queueManager->getCollectionToday($websiteId) as $queue) {
            switch ($queue->getData('profile_status')) {
                case ProfileStatus::STATUS_CANCELED:
                    continue 2;

                case ProfileStatus::STATUS_SUSPENDED:
                    $this->queueManager->makeSkipped($queue->getId(), __('Profile is Suspended, skipping...'));
                    break;

                case ProfileStatus::STATUS_COMPLETE:
                    $this->queueManager->makeSkipped($queue->getId(), __('Profile is Complete, skipping...'));
                    break;

                default:
                    $profileIds[] = $queue->getData('subscription_profile_id');

                    if ($this->passWithoutProcessing($queue)) {
                        continue 2;
                    }

                    $this->queueManager->makeRunning($queue->getId());

                    try {
                        $this->queueManager->processItem($queue);
                        $this->queueManager->makeCompleted($queue->getId());
                    } catch (\Exception $e) {
                        $this->context->messageError('Error on processing profile: %s', $e->getMessage());
                        $this->queueManager->makeError($queue->getId(), $e->getMessage());
                    }
                    break;
            }
        }

        $this->updateProfilesStatuses($profileIds);
    }

    /**
     * Updates profile statuses after queue processing.
     *
     * @param array $allIds
     */
    public function updateProfilesStatuses(array $allIds)
    {
        if (empty($allIds)) {
            return;
        }

        try {
            foreach ($this->statusProcessorsPool->getProcessorsInstances() as $modifier) {
                $modifier->process($allIds);
            }
        } catch (\Exception $e) {
            $this->context->messageError('Error on updating profile statuses. %s', $e->getMessage());
        }
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
