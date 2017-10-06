<?php

namespace TNW\Subscriptions\Model\Queue;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Queue;
use TNW\Subscriptions\Model\ResourceModel\Queue\Collection;
use TNW\Subscriptions\Model\ResourceModel\Queue\CollectionFactory;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Class Manager
 */
class Manager
{
    /**
     * Date format used on profile queue creating/processing.
     */
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Factory for creating queue collection.
     *
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Date conversion model.
     *
     * @var DateTime
     */
    private $date;

    /**
     * Config model.
     *
     * @var Config
     */
    private $config;

    /**
     * Profile manager.
     *
     * @var ProfileManager
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
     * Manager constructor.
     * @param CollectionFactory $collectionFactory
     * @param DateTime $date
     * @param Config $config
     * @param ProfileManager $profileManager
     * @param RelationManager $relationManager
     * @param CartRepositoryInterface $cartRepository
     * @param SubscriptionProfileRepository $profileRepository
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        DateTime $date,
        Config $config,
        ProfileManager $profileManager,
        RelationManager $relationManager,
        CartRepositoryInterface $cartRepository,
        SubscriptionProfileRepository $profileRepository
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->date = $date;
        $this->config = $config;
        $this->profileManager = $profileManager;
        $this->relationManager = $relationManager;
        $this->cartRepository = $cartRepository;
        $this->profileRepository = $profileRepository;
    }

    /**
     * Returns collection of queue items that can be processed.
     *
     * @param null|int $websiteId
     * @return Collection
     */
    public function getActiveList($websiteId = null)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->join(
            ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
            'main_table.profile_order_id = relation.id',
            [
                SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID,
                SubscriptionProfileOrderInterface::MAGENTO_QUOTE_ID,
                SubscriptionProfileOrderInterface::SCHEDULED_AT
            ]
        );
        $collection->getSelect()->join(
            ['profile' => SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY],
            'relation.subscription_profile_id = profile.entity_id',
            []
        );
        $connection = $collection->getConnection();
        $pendingCondition = implode(' AND ', [
            $connection->quoteInto("relation.scheduled_at <= ?", $this->getCurrentDate()),
            $connection->quoteInto(
                "main_table.status in (?)",
                [QueueStatus::QUEUE_STATUS_PENDING, QueueStatus::QUEUE_STATUS_RUNNING]
            )
        ]);
        $errorCondition = implode(' AND ', [
            $connection->quoteInto("main_table.updated_at <= ?", $this->getAttemptDate()),
            $connection->quoteInto("main_table.status = ?", QueueStatus::QUEUE_STATUS_ERROR),
            $connection->quoteInto("main_table.attempt_count <= ?", $this->config->getAttemptCount())
        ]);

        $collection->getSelect()->where(
            '(' . $pendingCondition . ') OR (' . $errorCondition . ')'
        )->where(
            'profile.status NOT IN (?)',
            [
                ProfileStatus::STATUS_CANCELED,
                ProfileStatus::STATUS_HOLDED,
                ProfileStatus::STATUS_SUSPENDED,
                ProfileStatus::STATUS_COMPLETE
            ]
        )->order(
            'relation.scheduled_at ASC'
        )->group(
            ['main_table.profile_order_id']
        );

        if ($websiteId) {
            $collection->getSelect()->where(
                'profile.website_id = ?', $websiteId
            );
        }

        return $collection;
    }

    /**
     * Inserts into queue new items.
     *
     * @param array $relationIds - ids from "tnw_subscriptions_subscription_profile_order" table
     * @param null|bool $makeProcessed
     * @return array
     */
    public function insertItems(
        $relationIds,
        $makeProcessed = null
    ) {
        if (!is_array($relationIds)) {
            $relationIds = [$relationIds];
        }
        $fields = [];
        $itemIds = [];
        $status = $makeProcessed ? QueueStatus::QUEUE_STATUS_RUNNING : QueueStatus::QUEUE_STATUS_PENDING;
        foreach ($relationIds as $relationId) {
            $fields[] = [
                Queue::PROFILE_ORDER_ID => $relationId,
                Queue::STATUS => $status,
                Queue::MESSAGE => '',
                Queue::CREATED_AT => $this->date->gmtDate(),
                Queue::UPDATED_AT => $this->date->gmtDate()
            ];
        }
        if (!empty($fields)) {
            /** @var Collection $collection */
            $collection = $this->collectionFactory->create();
            $collection->getConnection()->insertOnDuplicate(
                Queue::SUBSCRIPTION_PROFILE_QUEUE_TABLE,
                $fields,
                [Queue::MESSAGE, Queue::CREATED_AT, Queue::UPDATED_AT]
            );
            $itemIds = $collection->addFieldToFilter(
                Queue::PROFILE_ORDER_ID,
                ['in' => $relationIds]
            )->getAllIds();
        }

        return $itemIds;
    }

    /**
     * Changes status to running for queue items.
     *
     * @param array|int $ids
     */
    public function makeRunning($ids)
    {
        if (empty($ids)) {
            return;
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();
        $connection->update(
            $collection->getMainTable(),
            ['status' => QueueStatus::QUEUE_STATUS_RUNNING],
            [Queue::ID . ' in (?)' => $ids]
        );
    }

    /**
     * Changes status to error and sets error message for queue items.
     *
     * @param array|int $ids
     * @param string $message
     */
    public function makeError($ids, $message)
    {
        if (empty($ids)) {
            return;
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();
        $connection->update(
            $collection->getMainTable(),
            [
                'status' => QueueStatus::QUEUE_STATUS_ERROR,
                'attempt_count' => new \Zend_Db_Expr('attempt_count + 1'),
                'message' => $message,
                'updated_at' => $this->date->gmtDate(),
            ],
            [Queue::ID . ' in (?)' => $ids]
        );
    }

    /**
     * Changes status to synced queue items.
     *
     * @param array|int $ids
     */
    public function makeCompleted($ids)
    {
        if (empty($ids)) {
            return;
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();
        $connection->update(
            $collection->getMainTable(),
            [
                'status' => QueueStatus::QUEUE_STATUS_COMPLETE,
                'updated_at' => $this->date->gmtDate(),
                'message' => '',
                'attempt_count' => new \Zend_Db_Expr('attempt_count + 1'),
            ],
            [Queue::ID . ' in (?)' => $ids]
        );
    }

    /**
     * Returns formatted date for payment attempt.
     *
     * @return null|string
     */
    private function getAttemptDate()
    {
        $date = new \DateTime();
        $condition = 'P' . $this->config->getAttemptInterval() . 'D';
        $date->sub(new \DateInterval($condition));
        return $date->format(self::DATETIME_FORMAT);
    }

    /**
     * Returns formatted current date.
     *
     * @return null|string
     */
    private function getCurrentDate()
    {
        $date = new \DateTime();
        return $date->format(self::DATETIME_FORMAT);
    }

    /**
     * Processes queue item and add order to profile relation.
     *
     * @param Queue $item
     */
    public function processItem(Queue $item)
    {
        $profile = $this->profileRepository->getById($item->getSubscriptionProfileId());
        $quote = $this->cartRepository->get($item->getMagentoQuoteId());
        $order = $this->profileManager->setProfile($profile)
            ->processProfile($quote);
        $relation = $this->relationManager->getRelationById($item->getProfileOrderId())
            ->setMagentoOrderId($order->getId());
        $this->relationManager->saveRelation($relation);
    }
}
