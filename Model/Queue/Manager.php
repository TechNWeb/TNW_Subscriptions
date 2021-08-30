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
use TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManagerFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManager;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\Source\Queue\Status as QueueStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\EmailNotifierFactory;
use TNW\Subscriptions\Model\SubscriptionProfile\ReBillManager;

/**
 * Class Manager - queue manager model
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
     * @var \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation
     */
    private $relationResource;

    /**
     * @var EmailNotifierFactory
     */
    private $emailNotifierFactory;

    /**
     * @var BillingCyclesManagerFactory
     */
    private $billingCyclesManagerFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $orderSender;

    private $reBillManager;

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
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \TNW\Subscriptions\Model\ResourceModel\SalesItemRelation $relationResource,
        EmailNotifierFactory $emailNotifierFactory,
        BillingCyclesManagerFactory $billingCyclesManagerFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        ReBillManager $reBillManager
    ) {
        $this->reBillManager = $reBillManager;
        $this->orderSender = $orderSender;
        $this->billingCyclesManagerFactory = $billingCyclesManagerFactory;
        $this->emailNotifierFactory = $emailNotifierFactory;
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
        $this->relationResource = $relationResource;
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

        $pendingCondition = implode(' AND ', [
            $connection->prepareSqlCondition('relation.scheduled_at', [
                'from' => date('Y-m-d 00:00:00'),
                'to' => date('Y-m-d 23:59:59')
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
     * @param $daysBefore
     * @param array $additionalDate
     * @return Collection
     */
    public function getCollectionForDate($daysBefore, $additionalDate = [])
    {
        $collection = $this->getBaseCollection();
        $connection = $collection->getConnection();
        $searchableDate = strtotime(date('Y-m-d') . " +" . $daysBefore . " day");
        $pendingCondition = implode(' AND ', [
            $connection->prepareSqlCondition('relation.scheduled_at', [
                'from' => date('Y-m-d 00:00:00', $searchableDate),
                'to' => date('Y-m-d 23:59:59', $searchableDate)
            ]),
            $connection->prepareSqlCondition('main_table.status', QueueStatus::QUEUE_STATUS_PENDING),
        ]);
        if ($additionalDate) {
            $additionalSearchableDate = strtotime(date('Y-m-d') . " +" . $additionalDate . " day");
            $additionalPendingCondition = ' OR ' . implode(' AND ', [
                $connection->prepareSqlCondition('relation.scheduled_at', [
                    'from' => date('Y-m-d 00:00:00', $additionalSearchableDate),
                    'to' => date('Y-m-d 23:59:59', $additionalSearchableDate)
                ]),
                $connection->prepareSqlCondition('main_table.status', QueueStatus::QUEUE_STATUS_PENDING),
                ]);
            $pendingCondition = $pendingCondition . $additionalPendingCondition;
        }
        $collection->getSelect()
            ->where("$pendingCondition")
            ->order('relation.scheduled_at ASC')
            ->group(['main_table.profile_order_id']);

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
     * @param bool $isPaymentError
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeError($ids, $message, $isPaymentError = false)
    {
        $this->resourceQueue->updateStatus($ids, QueueStatus::QUEUE_STATUS_ERROR, $message, $isPaymentError);
    }

    /**
     * Changes status to reuquired verification and sets error message for queue items.
     *
     * @param array|int $ids
     * @param string $message
     * @param bool $isPaymentError
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makeVerification($ids, $message, $isPaymentError = false)
    {
        $this->resourceQueue->updateStatus($ids, QueueStatus::QUEUE_STATUS_VERIFICATION, $message, $isPaymentError);
    }

    /**
     * Changes status to pending and sets error message for queue items.
     *
     * @param array|int $ids
     * @param string $message
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function makePending($ids, $message)
    {
        $this->resourceQueue->updateStatus($ids, QueueStatus::QUEUE_STATUS_PENDING, $message);
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
            ->modify(sprintf('-%d day', $this->config->getAttemptInterval()))->setTime(23, 59, 59)
            ->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * @param $groupQueue
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     * @throws \TNW\Subscriptions\Exception\ProfileProductsUnsaleableException
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

        $outOfStockProducts = [];
        $countGroupQueue = count($groupQueue) - 1;
        $needTotalCount = false;
        foreach ($groupQueue as $index => $queue) {
            $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));
            if ($countGroupQueue == $index) {
                $needTotalCount = true;
            }
            $outOfStockProducts[$profile->getId()] =
                $this->profileManager->populateQuoteData($quote, $profile, $needTotalCount, true);
        }

        foreach ($outOfStockProducts as $profileId => $outOfStockProductData) {
            $profile = $this->profileRepository->getById($profileId);
            /** Setting the actual billing address from the profile
             * for the quote no matter whether the address is
             * saved in the address book or not
             */
            $quote->getBillingAddress()->addData($profile->getBillingAddress()->getData());
            $quote->getBillingAddress()->setCustomerId(
                $profile->getCustomerId()
            );
            
            if ($outOfStockProductData && is_array($outOfStockProductData)) {
                try {
                    $this->emailNotifierFactory->create()->outOfStockProducts($profile, $outOfStockProductData);
                } catch (\Exception $e) {
                    $this->messageHistoryLogger->log(
                        __('Some error sending out of stock notification.'),
                        $profile->getId(),
                        false,
                        false,
                        true
                    );
                }
                $this->messageHistoryLogger->message(
                    SubscriptionProfile\MessageHistoryLogger::MESSAGE_SUBSCRIPTION_PRODUCT_STOCK,
                    [
                        implode(', ', $outOfStockProductData)
                    ],
                    $profile->getId(),
                    false,
                    true,
                    true
                );
            }
        }
        if ($quote->getAllVisibleItems()) {
            $this->cartRepository->save($quote);
            $quote->getPayment()->setAdditionalInformation('is_rebill', true);
            try {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->profileManager
                    ->getEngine()
                    ->getCartManagement()
                    ->submit($quote);
                $this->orderSender->send($order);

                $insertData = [];
                foreach ($quote->getAllVisibleItems() as $item) {
                    $profileItemIds = $item->getData('profile_item_ids');
                    if (empty($profileItemIds)) {
                        continue;
                    }

                    $orderItem = $order->getItemByQuoteItemId($item->getId());
                    if (!$orderItem instanceof \Magento\Sales\Model\Order\Item) {
                        continue;
                    }

                    foreach ($profileItemIds as $profileItemId) {
                        $insertData[] = [
                            'profile_item_id' => $profileItemId,
                            'quote_item_id' => $item->getId(),
                            'order_item_id' => $orderItem->getItemId(),
                        ];
                    }
                }

                // Save Items Relation
                $this->relationResource->insertSales($insertData);

                foreach ($groupQueue as $queue) {
                    $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));

                    $oldStatus = $profile->getStatus();

                    $profile->setStatus(ProfileStatus::STATUS_ACTIVE);
                    if ($profile->getTrialStartDate() && time() < strtotime($profile->getStartDate())) {
                        $profile->setStatus(ProfileStatus::STATUS_TRIAL);
                    }

                    $this->profileManager->setProfile($profile);
                    $this->profileManager->saveProfile();

                    if ($oldStatus != $profile->getStatus()) {
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

                    //Add comment profile place.
                    $this->messageHistoryLogger->message(
                        SubscriptionProfile\MessageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE,
                        [
                            $order->getEntityId(),
                            $order->getIncrementId(),
                            $this->messageHistoryLogger->getConvertedQuoteId($quote->getId())
                        ],
                        $profile->getId(),
                        false,
                        false,
                        true
                    );

                    if ($quote->getCouponCode() === "") {
                        $this->messageHistoryLogger->message(
                            SubscriptionProfile\MessageHistoryLogger::COUPON_INVALID,
                            [
                                $profile->getCouponCode()
                            ],
                            $profile->getId(),
                            false,
                            false,
                            true
                        );
                    }

                    $relation = $this->relationManager
                        ->getRelationById($queue->getProfileOrderId())
                        ->setMagentoQuoteId($quote->getId())
                        ->setMagentoOrderId($order->getId());

                    $this->relationManager->saveRelation($relation);
                    $this->createNewRelation($queue, $profile);
                    $profile->setTotalBillingCycles($profile->getTotalBillingCycles() - 1);
                    $profile->setNeedRecollect(false);
                    foreach ($profile->getProducts() as $product) {
                        $product->setNeedRecollect(false);
                    }
                    $this->profileRepository->save($profile);
                }
            } catch (\Exception $e) {
                if ($e instanceof \Magento\Payment\Gateway\Command\CommandException && $e->getCode() == 2099) {
                    $reBill = $this->reBillManager->createReBillByFailedGroupQueue($groupQueue);
                }
                foreach ($groupQueue as $queue) {
                    $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));

                    $oldStatus = $profile->getStatus();
                    $profile->setStatus(ProfileStatus::STATUS_PAST_DUE);
                    $this->profileRepository->save($profile);

                    if ($oldStatus != $profile->getStatus()) {
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
                    if ($e instanceof \Magento\Payment\Gateway\Command\CommandException) {
                        if (isset($reBill)) {
                            $this->emailNotifierFactory->create()->paymentVerificationFailed($profile, $reBill);
                        } else {
                            $this->emailNotifierFactory->create()->paymentFailed($profile);
                        }
                    }
                }
                throw $e;
            }
        } else {
            foreach ($groupQueue as $queue) {
                $profile = $this->profileRepository->getById($queue->getData('subscription_profile_id'));
                $this->createNewRelation($queue, $profile);
            }
            throw new \TNW\Subscriptions\Exception\ProfileProductsUnsaleableException(
                __('There were no saleable products in queue profiles.')
            );
        }
    }

    /**
     * @param $queue
     * @param $profile
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createNewRelation($queue, $profile)
    {
        /** @var BillingCyclesManager $billingCyclesManager */
        $billingCyclesManager = $this->billingCyclesManagerFactory->create();
        list($cycles, $needMore, $existingCycles) =
            $billingCyclesManager->getBillingCycles($profile, 1, true);
        if ($needMore && $cycles) {
            $relations = [];
            foreach ($cycles as $cycle) {
                $newRelation = $this->relationManager->getNewProfileOrderRelation()
                    ->setSubscriptionProfileId($profile->getId())
                    ->setMagentoQuoteId($queue->getData('magento_quote_id'))
                    ->setScheduledAt($cycle);
                $relations[] = $this->relationManager->saveRelation($newRelation)->getId();
            }
            if ($relations) {
                $this->insertItems($relations);
            }
        }
    }

    /**
     * @param $quoteId
     * @param $profile
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createNewRelationByQuoteId($quoteId, $profile)
    {
        /** @var BillingCyclesManager $billingCyclesManager */
        $billingCyclesManager = $this->billingCyclesManagerFactory->create();
        list($cycles, $needMore, $existingCycles) =
            $billingCyclesManager->getBillingCycles($profile, 1, true);
        if ($needMore && $cycles) {
            $relations = [];
            foreach ($cycles as $cycle) {
                $newRelation = $this->relationManager->getNewProfileOrderRelation()
                    ->setSubscriptionProfileId($profile->getId())
                    ->setMagentoQuoteId($quoteId)
                    ->setScheduledAt($cycle);
                $relations[] = $this->relationManager->saveRelation($newRelation)->getId();
            }
            if ($relations) {
                return $relations;
            }
        }
        return [];
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
        if ($status == ProfileStatus::STATUS_HOLDED && !$item->getData('billing_cycles_to_skip')) {
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
                    SubscriptionProfile::BILLING_CYCLES_TO_SKIP
                ]
            );

        return $collection;
    }
}
