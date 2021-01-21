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
     * @param SubscriptionProfileRepository $profileRepository
     * @param StatusManager $statusManager
     * @param ProfileStatus $statusSource
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        StatusManager $statusManager,
        ProfileStatus $statusSource,
        MessageHistoryLogger $messageHistoryLogger,
        ManagerInterface $messageManager
    ) {
        $this->profileRepository = $profileRepository;
        $this->statusManager = $statusManager;
        $this->statusSource = $statusSource;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->messageManager = $messageManager;
    }

    /** Update status.
     *
     * @param int $profileId
     * @param int $newStatus
     */
    public function updateStatus($profileId, $newStatus)
    {
        /* @var SubscriptionProfile $model */
        $model = $this->profileRepository->getById($profileId);

        if (
            !$this->statusManager->canChangeStatus($model, $newStatus)
            || (int) $model->getData('status') === ProfileStatus::STATUS_TRIAL
        ) {            $this->messageManager->addErrorMessage(
                __('Status can not be change to "%1"', $this->statusSource->getLabelByValue($newStatus))
            );
            $model = null;
        } else {
            $oldStatus = $model->getStatus();
            $model->setStatus($newStatus);
            $this->profileRepository->save($model);
            $this->logChangeStatus($model, $oldStatus);

            $this->messageManager->addSuccessMessage(__(
                'Status successfully changed to "%1"',
                $this->statusSource->getLabelByValue($newStatus)
            ));
        }
        return $model;
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

    /**
     * Set cancel_before_next_cycle to 1 for subscription profile.
     *
     * @param int $profileId
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
}
