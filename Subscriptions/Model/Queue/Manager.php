<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Queue;

use Magento\Quote\Api\CartRepositoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\ResourceModel\Queue\Collection;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Manager
 */
class Manager
{
    /**
     * Factory for creating queue collection.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Config model.
     *
     * @var Config
     */
    private $config;

    /**
     * Profile manager.
     *
     * @var SubscriptionProfile\Manager
     */
    private $profileManager;

    /**
     * Profile relation manager.
     *
     * @var RelationManager
     */
    private $relationManager;

    /**
     * Repository fore saving/retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * Subscription profile status history manager.
     *
     * @var SubscriptionProfile\Status\HistoryManager
     */
    private $statusHistoryManager;

    /**
     * @var SubscriptionProfile\MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @var ProfileStatus
     */
    private $profileStatus;

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\Queue
     */
    private $resourceQueue;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    private $quoteFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     * @param SubscriptionProfile\Manager $profileManager
     * @param RelationManager $relationManager
     * @param CartRepositoryInterface $cartRepository
     * @param SubscriptionProfileRepository $profileRepository
     * @param SubscriptionProfile\Status\HistoryManager $statusHistoryManager
     * @param SubscriptionProfile\MessageHistoryLogger $messageHistoryLogger
     * @param ProfileStatus $profileStatus
     * @param \TNW\Subscriptions\Model\ResourceModel\Queue $resourceQueue
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Config $config,
        SubscriptionProfile\Manager $profileManager,
        RelationManager $relationManager,
        CartRepositoryInterface $cartRepository,
        SubscriptionProfileRepository $profileRepository,
        SubscriptionProfile\Status\HistoryManager $statusHistoryManager,
        SubscriptionProfile\MessageHistoryLogger $messageHistoryLogger,
        ProfileStatus $profileStatus,
        \TNW\Subscriptions\Model\ResourceModel\Queue $resourceQueue,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->profileManager = $profileManager;
        $this->relationManager = $relationManager;
        $this->cartRepository = $cartRepository;
        $this->profileRepository = $profileRepository;
        $this->statusHistoryManager = $statusHistoryManager;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->profileStatus = $profileStatus;
        $this->resourceQueue = $resourceQueue;
        $this->timezone = $timezone;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * Returns collection of queue items that can be processed.
     *
     * @param null|int $websiteId
     * @return Collection
     */
    public function getActiveList($websiteId = null)
    {
        $collection = $this->getCollectionToday($websiteId);
        $collection->getSelect()
            ->where(
                'profile.status NOT IN (?)',
                [
                    ProfileStatus::STATUS_CANCELED,
                    ProfileStatus::STATUS_SUSPENDED,
                    ProfileStatus::STATUS_COMPLETE,
                ]
            );

        return $collection;
    }

    /**
     * @param int|null $websiteId
     *
     * @return Collection
     */
    public function getCollectionToday($websiteId = null)
    {
        $collection = $this->getBaseCollection();
        $connection = $collection->getConnection();
        $currentDate = $this->timezone->date();

        $pendingCondition = implode(' AND ', [
            $connection->prepareSqlCondition('relation.scheduled_at', [
                'from' => $currentDate->format('Y-m-d 00:00:00'),
                'to' => $currentDate->format('Y-m-d 23:59:59')
            ]),
            $connection->prepareSqlCondition('main_table.status', QueueStatus::QUEUE_STATUS_PENDING),
        ]);

        $otherCondition = implode(' AND ', [
            $connection->quoteInto('main_table.updated_at <= ?', $this->getAttemptDate()),
            $connection->quoteInto('main_table.status IN (?)', [
                QueueStatus::QUEUE_STATUS_ERROR,
                QueueStatus::QUEUE_STATUS_SKIPPED
            ]),
            $connection->quoteInto('main_table.attempt_count <= ?', $this->config->getAttemptCount()),
        ]);

        $collection->getSelect()
            ->where("($pendingCondition) OR ($otherCondition)")
            ->order('relation.scheduled_at ASC')
            ->group(['main_table.profile_order_id']);

        if (null !== $websiteId) {
            $collection->getSelect()
                ->where('profile.website_id = ?', $websiteId);
        }

        return $collection;
    }

    /**
     * Inserts into queue new items.
     *
     * @param array $relationIds - ids from "tnw_subscriptions_subscription_profile_order" table
     * @param null|bool $makeProcessed
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @deprecated
     * @see \TNW\Subscriptions\Model\ResourceModel\Queue::insertItems
     */
    public function insertItems(
        $relationIds,
        $makeProcessed = null
    ) {
        return $this->resourceQueue->insertItems($relationIds, $makeProcessed);
    }

    /**
     * @param int[]|int $ids
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @deprecated
     * @see \TNW\Subscriptions\Model\ResourceModel\Queue::deleteIds
     */
    public function makeDelete($ids)
    {
        return $this->resourceQueue->deleteIds($ids);
    }

    /**
     * Changes status to running for queue items.
     *
     * @param array|int $ids
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeRunning($ids)
    {
        $this->resourceQueue->updateStatus($ids, QueueStatus::QUEUE_STATUS_RUNNING);
    }

    /**
     * Changes status to error and sets error message for queue items.
     *
     * @param array|int $ids
     * @param string $message
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeError($ids, $message)
    {
        $this->resourceQueue->updateStatus($ids, QueueStatus::QUEUE_STATUS_ERROR, $message);
    }

    /**
     * Changes status to synced queue items.
     *
     * @param array|int $ids
     * @param string $message
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeCompleted($ids, $message = '')
    {
        $this->resourceQueue->updateStatus($ids, QueueStatus::QUEUE_STATUS_COMPLETE, $message);
    }

    /**
     * Changes status to synced queue items.
     *
     * @param array|int $ids
     * @param string $message
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeSkipped($ids, $message = '')
    {
        $this->resourceQueue->updateStatus($ids, QueueStatus::QUEUE_STATUS_SKIPPED, $message);
    }

    /**
     * Returns formatted date for payment attempt.
     *
     * @return null|string
     */
    private function getAttemptDate()
    {
        return $this->timezone->date()
            ->modify(sprintf('-%d day', $this->config->getAttemptInterval()))
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * Processes queue item and add order to profile relation.
     * Return true if queue item need to be post-processed.
     *
     * @param Queue $item
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processItem(Queue $item)
    {
        $profile = $this->profileRepository->getById($item->getSubscriptionProfileId());
        $quote = $this->cartRepository->get($item->getMagentoQuoteId());

        if ($this->itemOnHold($item)) {
            return false;
        }

        $oldStatus = $profile->getStatus();

        try {
            $order = $this->profileManager->setProfile($profile)
                ->processProfile($quote);
        } finally {
            $this->profileRepository->save($profile);

            $newStatus = $profile->getStatus();
            if ($oldStatus != $newStatus) {
                //Add comment profile place.
                $this->messageHistoryLogger->message(
                    SubscriptionProfile\MessageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED,
                    [
                        $this->profileStatus->getLabelByValue($oldStatus),
                        $this->profileStatus->getLabelByValue($newStatus)
                    ],
                    $profile->getId(),
                    false,
                    false,
                    true
                );
            }
        }

        //Add comment profile place.
        $this->messageHistoryLogger->message(
            SubscriptionProfile\MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE,
            [
                $order->getIncrementId(),
                $this->messageHistoryLogger->getConvertedQuoteId($quote->getId())
            ],
            $profile->getId(),
            false,
            false,
            true
        );

        $relation = $this->relationManager->getRelationById($item->getProfileOrderId())
            ->setMagentoOrderId($order->getId());
        $this->relationManager->saveRelation($relation);

        return true;
    }

    /**
     * @param \TNW\Subscriptions\Model\Queue[] $groupQueue
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     * @throws \Exception
     */
    public function placeOrderByGroupQueue($groupQueue)
    {
        // filter item on hold
        $groupQueue = array_filter($groupQueue, [$this, 'filterItemOnHold']);
        if (empty($groupQueue)) {
            return;
        }

        $quote = $this->quoteFactory->create(['data' => ['is_active' => false]]);
        $this->cartRepository->save($quote);

        foreach ($groupQueue as $queue) {
            $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));
            $this->profileManager->populateQuoteData($quote, $profile);
        }

        $this->cartRepository->save($quote);

        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->profileManager
                ->getEngine()
                ->getCartManagement()
                ->submit($quote);

            foreach ($groupQueue as $queue) {
                $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));

                $oldStatus = $profile->getStatus();

                $profile->setStatus(ProfileStatus::STATUS_ACTIVE);
                if ($profile->getTrialStartDate() && time() < strtotime($profile->getStartDate())) {
                    $profile->setStatus(ProfileStatus::STATUS_TRIAL);
                }

                $this->profileRepository->save($profile);

                //Add comment profile place.
                $this->messageHistoryLogger->message(
                    SubscriptionProfile\MessageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED,
                    [
                        $this->profileStatus->getLabelByValue($oldStatus),
                        $this->profileStatus->getLabelByValue($profile->getStatus())
                    ],
                    $profile->getId(),
                    false,
                    false,
                    true
                );

                //Add comment profile place.
                $this->messageHistoryLogger->message(
                    SubscriptionProfile\MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE,
                    [
                        $order->getIncrementId(),
                        $this->messageHistoryLogger->getConvertedQuoteId($quote->getId())
                    ],
                    $profile->getId(),
                    false,
                    false,
                    true
                );

                $relation = $this->relationManager
                    ->getRelationById($queue->getProfileOrderId())
                    ->setMagentoQuoteId($quote->getId())
                    ->setMagentoOrderId($order->getId());

                $this->relationManager->saveRelation($relation);
            }
        } catch (\Exception $e) {
            foreach ($groupQueue as $queue) {
                $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));

                $oldStatus = $profile->getStatus();
                $profile->setStatus(ProfileStatus::STATUS_PAST_DUE);
                $this->profileRepository->save($profile);

                //Add comment profile place.
                $this->messageHistoryLogger->message(
                    SubscriptionProfile\MessageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED,
                    [
                        $this->profileStatus->getLabelByValue($oldStatus),
                        $this->profileStatus->getLabelByValue($profile->getStatus())
                    ],
                    $profile->getId(),
                    false,
                    false,
                    true
                );
            }

            throw $e;
        }
    }

    /**
     * @param Queue $item
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function filterItemOnHold(Queue $item)
    {
        return !$this->itemOnHold($item);
    }

    /**
     * Check if queue item subscription profile was on hold for order item schedule time.
     *
     * @param Queue $item
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function itemOnHold(Queue $item)
    {
        $result = false;
        $status = $this->statusHistoryManager->getStatusForTime(
            $item->getSubscriptionProfileId(),
            $item->getScheduledAt()
        );
        if ($status == ProfileStatus::STATUS_HOLDED) {
            // Set subscription profile order quote ID field to null
            $order = $this->relationManager->getRelationById($item->getProfileOrderId());
            $order->setMagentoQuoteId(null);
            $this->relationManager->saveRelation($order);
            // Remove corresponding magento quote
            $quote = $this->cartRepository->get($item->getMagentoQuoteId());
            $this->cartRepository->delete($quote);
            $result = true;
        }

        return $result;
    }

    /**
     * Returns base collection.
     *
     * @return Collection
     */
    public function getBaseCollection()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create()
            ->join(
                ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
                'main_table.profile_order_id = relation.id AND relation.magento_quote_id IS NOT NULL',
                [
                    SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID,
                    SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                    SubscriptionProfileOrderInterface::SCHEDULED_AT,
                ]
            )
            ->join(
                ['profile' => SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY],
                'relation.subscription_profile_id = profile.entity_id',
                [
                    SubscriptionProfile::CANCEL_BEFORE_NEXT_CYCLE,
                    'profile_' . SubscriptionProfile::STATUS => SubscriptionProfile::STATUS,
                ]
            );

        return $collection;
    }
}
