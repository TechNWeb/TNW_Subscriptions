<?php

namespace TNW\Subscriptions\Model\Queue;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
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
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;


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
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param CartRepositoryInterface $cartRepository
     * @param SubscriptionProfileRepository $profileRepository
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        DateTime $date,
        Config $config,
        ProfileManager $profileManager,
        RelationManager $relationManager,
        SearchCriteriaBuilder $criteriaBuilder,
        CartRepositoryInterface $cartRepository,
        SubscriptionProfileRepository $profileRepository
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->date = $date;
        $this->config = $config;
        $this->profileManager = $profileManager;
        $this->relationManager = $relationManager;
        $this->criteriaBuilder = $criteriaBuilder;
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
        $completeStatus = QueueStatus::QUEUE_STATUS_COMPLETE;
        $errorStatus = QueueStatus::QUEUE_STATUS_ERROR;
        $collection->getSelect()->where(
            "relation.scheduled_at <= '{$this->getCurrentDate()}' AND main_table.status != '{$completeStatus}'"
        )->orWhere(
            "main_table.updated_at <= '{$this->getAttemptDate()}' AND main_table.status = '{$errorStatus}'"
        )->where(
            'main_table.attempt_count <= ?', $this->config->getAttemptCount()
        )->where(
            'profile.status NOT IN (?)',
            [
                ProfileStatus::STATUS_CANCELED,
                ProfileStatus::STATUS_HOLDED
            ]
        )->order(
            'relation.scheduled_at ASC'
        )->group(
            ['main_table.profile_order_id']
        );

        if ($websiteId){
            $collection->getSelect()->where(
                'profile.website_id = ?', $websiteId
            );
        }

        return $collection;
    }

    /**
     * Returns list of profile ids that should be transferred to status "Suspended".
     *
     * @return array
     */
    public function getSuspendedProfileIds()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->join(
                ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
                'main_table.profile_order_id = relation.id',
                [SubscriptionProfileOrderInterface::SUBSCRIPTION_PROFILE_ID]
            );
        $collection->getSelect()
            ->where(
                'relation.scheduled_at <= ?', $this->getSuspendDate()
            )->orWhere(
                'main_table.attempt_count >= ?', $this->config->getAttemptCount()
            );

        return $collection->getConnection()->fetchAll(
            $collection->getSelect()
        );
    }

    /**
     * Returns list of profile ids that should be transferred to status "Canceled".
     *
     * @return array
     */
    public function getCanceledProfileIds()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->getSelect()
            ->reset()
            ->from(
                ['relation' => SubscriptionProfileOrderInterface::MAIN_TABLE],
                []
            )->join(
                ['profile' => SubscriptionProfile::SUBSCRIPTION_PROFILE_ENTITY],
                'relation.subscription_profile_id = profile.entity_id',
                [SubscriptionProfile::ID, SubscriptionProfile::TOTAL_BILLING_CYCLES]
            )->where(
                'profile.term = ?', 0
            )->where(
                'relation.magento_order_id IS NOT NULL'
            )->group(
                ['relation.subscription_profile_id']
            )->having(
                'COUNT(relation.subscription_profile_id) = profile.total_billing_cycles'
            );

        return $collection->getConnection()->fetchCol(
            $collection->getSelect()
        );
    }

    /**
     * Inserts into queue new items.
     *
     * @param array $relationIds - ids from "tnw_subscriptions_subscription_profile_order" table
     */
    public function insertItems(
        $relationIds
    ) {
        if (!is_array($relationIds)) {
            $relationIds = [$relationIds];
        }
        $fields = [];
        foreach ($relationIds as $relationId) {
            $fields[] = [
                Queue::PROFILE_ORDER_ID => $relationId,
                Queue::STATUS => QueueStatus::QUEUE_STATUS_PENDING,
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
        }
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
        return $date->format('Y-m-d');
    }

    /**
     * Returns formatted current date.
     *
     * @return null|string
     */
    private function getCurrentDate()
    {
        $date = new \DateTime();
        return $date->format('Y-m-d');
    }

    /**
     * Returns formatted profile suspend date.
     *
     * @return null|string
     */
    private function getSuspendDate()
    {
        $date = new \DateTime();
        $condition = 'P' . $this->config->getGracePeriod() . 'D';
        $date->sub(new \DateInterval($condition));
        return $date->format('Y-m-d');
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

    /**
     * Updates status in profiles ("Suspended" and "Canceled").
     */
    public function updateProfilesStatuses()
    {
        $suspendedIds = $this->getSuspendedProfileIds();
        $canceledIds = $this->getCanceledProfileIds();
        $this->criteriaBuilder->addFilter(
            SubscriptionProfile::ID,
            array_merge($suspendedIds, $canceledIds),
            'in'
        );
        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->criteriaBuilder->create();
        $profiles = $this->profileRepository->getList($searchCriteria)->getItems();
        /** @var SubscriptionProfile $profile */
        foreach ($profiles as $profile) {
            $this->profileManager->setProfile($profile);
            //update status in each profile
            if (in_array($profile->getId(), $suspendedIds)) {
                $this->profileManager->setSuspendedStatus();
            } elseif (in_array($profile->getId(), $canceledIds)) {
                $this->profileManager->setCanceledStatus();
            }
            $this->profileManager->saveProfile();
        }
    }
}
