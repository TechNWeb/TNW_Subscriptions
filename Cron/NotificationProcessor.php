<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Cron;

use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\PoolInterface;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\EmailNotifierFactory;

class NotificationProcessor
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
     * Profile Repository
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    private $emailNotifierFactory;

    public function __construct(
        Context $context,
        Manager $queueManager,
        PoolInterface $statusProcessorsPool,
        SubscriptionProfileRepository $profileRepository,
        EmailNotifierFactory $emailNotifierFactory
    ) {
        $this->emailNotifierFactory = $emailNotifierFactory;
        $this->context = $context;
        $this->queueManager = $queueManager;
        $this->statusProcessorsPool = $statusProcessorsPool;
        $this->profileRepository = $profileRepository;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $canceledCollection = $this->queueManager->getBaseCollection()
            ->addFieldToFilter('profile.status', ProfileStatus::STATUS_ACTIVE)
            ->addFieldToFilter('main_table.status', QueueStatus::QUEUE_STATUS_PENDING);
        // Queue collection
        $collectionToday = $this->queueManager->getCollectionForDate(1);
        $notificator = $this->emailNotifierFactory->create();
    }
}
