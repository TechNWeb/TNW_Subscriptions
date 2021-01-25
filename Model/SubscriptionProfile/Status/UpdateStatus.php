<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile\Status;

use Magento\Framework\Message\ManagerInterface;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;
use TNW\Subscriptions\Api\SubscriptionProfileQueueRepositoryInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\BillingCyclesManagerFactory;
use TNW\Subscriptions\Model\Source\Queue\Status;

/**
 * Update status for subscription profile model.
 */
class UpdateStatus
{
    /**
     * Repository profile.
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * The Manager that define logic of status change on Subscription Profile.
     *
     * @var StatusManager
     */
    private $statusManager;

    /**
     * Profile status data source.
     *
     * @var ProfileStatus
     */
    private $statusSource;

    /**
     * Message history logger.
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * Message Manager.
     *
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var BillingCyclesManagerFactory
     */
    private $billingCyclesManagerFactory;

    /**
     * @var ProfileOrderManager
     */
    private $profileOrderManager;

    private $profileQueueRepository;

    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        StatusManager $statusManager,
        ProfileStatus $statusSource,
        MessageHistoryLogger $messageHistoryLogger,
        ManagerInterface $messageManager,
        BillingCyclesManagerFactory $billingCyclesManagerFactory,
        ProfileOrderManager $profileOrderManager,
        SubscriptionProfileQueueRepositoryInterface $profileQueueRepository
    ) {
        $this->profileRepository = $profileRepository;
        $this->statusManager = $statusManager;
        $this->statusSource = $statusSource;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->messageManager = $messageManager;
        $this->profileOrderManager = $profileOrderManager;
        $this->billingCyclesManagerFactory = $billingCyclesManagerFactory;
        $this->profileQueueRepository = $profileQueueRepository;
    }

    /**
     * Update Subscription Profile Status
     * @param $profileId
     * @param $newStatus
     * @param int $billingCycles
     * @return SubscriptionProfile|null
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateStatus($profileId, $newStatus, $billingCycles = 0)
    {
        /* @var SubscriptionProfile $model */
        $model = $this->profileRepository->getById($profileId);

        if (!$this->statusManager->canChangeStatus($model, $newStatus)
            || (int) $model->getData('status') === ProfileStatus::STATUS_TRIAL
        ) {
            $this->messageManager->addErrorMessage(
                __('Status can not be change to "%1"', $this->statusSource->getLabelByValue($newStatus))
            );
            $model = null;
        } else {
            $oldStatus = $model->getStatus();
            $model->setStatus($newStatus);
            $model->setSkipBillingCycles($billingCycles);
            $this->profileRepository->save($model);
            $this->processBillingCyclesOnStatusChange($newStatus, $oldStatus, $model);
            $this->logChangeStatus($model, $oldStatus);

            $this->messageManager->addSuccessMessage(__(
                'Status successfully changed to "%1"',
                $this->statusSource->getLabelByValue($newStatus)
            ));
        }
        return $model;
    }

    /**
     * @param $profileId
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateStatusBeforeNextBillingCycle($profileId)
    {
        /* @var SubscriptionProfile $model */
        $model = $this->profileRepository->getById($profileId);

        $model->setData(SubscriptionProfile::CANCEL_BEFORE_NEXT_CYCLE, 1);

        $this->profileRepository->save($model);

        $this->messageManager->addSuccessMessage(__(
            'Status will be changed before next billing cycle.'
        ));
    }

    /**
     * @param $newStatus
     * @param $oldStatus
     * @param $subscriptionProfile
     * @return $this
     * @throws \Exception
     */
    private function processBillingCyclesOnStatusChange($newStatus, $oldStatus, $subscriptionProfile)
    {
        if ($newStatus == ProfileStatus::STATUS_HOLDED
            && in_array(
                $oldStatus,
                [
                    ProfileStatus::STATUS_ACTIVE,
                    ProfileStatus::STATUS_TRIAL,
                    ProfileStatus::STATUS_PENDING
                ]
            )
            && $subscriptionProfile->getSkipBillingCycles()
        ) {
            $billingCyclesToSkip = $subscriptionProfile->getSkipBillingCycles();
            $this->processSaveRelation($billingCyclesToSkip, $subscriptionProfile, $billingCyclesToSkip + 1);
        } elseif ($newStatus == ProfileStatus::STATUS_ACTIVE && $oldStatus) {
            $this->processSaveRelation(0, $subscriptionProfile, 1);
        }
        return $this;
    }

    /**
     * @param $cycleNumberToSchedule
     * @param $subscriptionProfile
     * @param $cyclesToSkip
     * @return bool
     * @throws \Exception
     */
    private function processSaveRelation($cycleNumberToSchedule, $subscriptionProfile, $cyclesToSkip)
    {
        $nextProfileRelation = $this->profileOrderManager->getNextProfileRelation($subscriptionProfile);
        $rescheduled = false;
        list($cycles, $needMore, $existingCycles) = $this->billingCyclesManagerFactory->create()->getBillingCycles(
            $subscriptionProfile,
            $cyclesToSkip,
            true,
            true
        );
        if ($cycles && is_array($cycles) && array_key_exists($cycleNumberToSchedule, $cycles)) {
            $nextProfileRelation->setScheduledAt($cycles[$cycleNumberToSchedule]);
            $rescheduled = true;
            try {
                $this->profileOrderManager->saveRelation($nextProfileRelation);
                $queue = $this->profileQueueRepository->retrieveByRelationId($nextProfileRelation->getId());
                if (in_array(
                    $queue->getStatus(),
                    [
                        Status::QUEUE_STATUS_COMPLETE,
                        Status::QUEUE_STATUS_ERROR,
                        Status::QUEUE_STATUS_SKIPPED,
                    ]
                )) {
                    $queue->setStatus(Status::QUEUE_STATUS_PENDING);
                    $queue->setAttemptCount(0);
                    $queue->setMessage('');
                    $this->profileQueueRepository->save($queue->setStatus(Status::QUEUE_STATUS_PENDING));
                }
            } catch (\Exception $e) {
                $rescheduled = false;
            }
        }
        if (!$rescheduled) {
            $this->messageHistoryLogger->log(
                __(
                    'Profile won`t process anymore - paused for billing cycles exceeds '
                    . 'the possible payments/orders limit.'
                ),
                $subscriptionProfile->getId(),
                true,
                true,
                false
            );
        }
        return $rescheduled;
    }


    /**
     * Log change status in to Subscription Profile history.
     *
     * @param SubscriptionProfile $model
     * @param int $oldStatus
     *
     * @return void
     */
    private function logChangeStatus(SubscriptionProfile $model, $oldStatus)
    {
        if ($oldStatus == $model->getStatus()) {
            return;
        }

        $this->messageHistoryLogger->message(
            MessageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED,
            [
                $this->statusSource->getLabelByValue($oldStatus),
                $this->statusSource->getLabelByValue($model->getStatus())
            ],
            $model->getId()
        );
    }
}
