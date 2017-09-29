<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Actions;

use Magento\Framework\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;

/**
 * Controller for subscription history at customer account dashboard.
 */
class UpdateStatus extends \Magento\Framework\App\Action\Action
{
    /**
     * Repository profile
     *
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;

    /**
     * The Manager that define logic of status change on Subscription Profile
     *
     * @var StatusManager
     */
    private $statusManager;

    /**
     * Profile status data source
     *
     * @var ProfileStatus
     */
    private $statusSource;

    /**
     * Message history logger
     *
     * @var MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @param Context $context
     * @param SubscriptionProfileRepository $profileRepository
     * @param StatusManager $statusManager
     * @param ProfileStatus $statusSource
     * @param MessageHistoryLogger $messageHistoryLogger
     */
    public function __construct(
        Context $context,
        SubscriptionProfileRepository $profileRepository,
        StatusManager $statusManager,
        ProfileStatus $statusSource,
        MessageHistoryLogger $messageHistoryLogger
    ) {
        $this->profileRepository = $profileRepository;
        $this->statusManager = $statusManager;
        $this->statusSource = $statusSource;
        $this->messageHistoryLogger = $messageHistoryLogger;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('entity_id');
        $newStatus = $this->getRequest()->getParam('status');

        try {
            /* @var SubscriptionProfile $model */
            $model = $this->profileRepository->getById($profileId);

            if (!$this->statusManager->canChangeStatus($model, $newStatus)) {
                $this->messageManager->addErrorMessage(
                    __('Status can not be change to "%1"', $this->statusSource->getLabelByValue($newStatus))
                );
                return $this->getRedirect();
            }

            $oldStatus = $model->getStatus();
            $model->setStatus($newStatus);
            $this->profileRepository->save($model);
            $this->logChangeStatus($model, $oldStatus);

            $this->messageManager->addSuccessMessage(__(
                'Status successfully changed to "%1"',
                $this->statusSource->getLabelByValue($newStatus)
            ));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->getRedirect();
        }

        return $this->getRedirect();
    }

    /**
     * Retrieve redirect model
     *
     * @return Redirect
     */
    private function getRedirect()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/subscription/history');
    }

    /**
     * Log change status in to Subscription Profile history
     *
     * @param SubscriptionProfile $model
     * @param int $oldStatus
     * @return void
     */
    private function logChangeStatus(SubscriptionProfile $model, $oldStatus)
    {
        $message = sprintf(
            $this->messageHistoryLogger->getMessage(MessageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED),
            $this->statusSource->getLabelByValue($oldStatus),
            $this->statusSource->getLabelByValue($model->getStatus())
        );

        $this->messageHistoryLogger->log($message, $model->getId());
    }
}
