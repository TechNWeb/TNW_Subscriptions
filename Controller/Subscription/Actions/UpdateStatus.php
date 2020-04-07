<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Controller\Subscription\Actions;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\Context;
use TNW\Subscriptions\Block\Subscription\History;
use TNW\Subscriptions\Block\Subscription\Summary\Overview;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\SubscriptionProfile\StatusManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Controller\Subscription\Items;

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
     * Subscription items at customer account
     *
     * @var Items
     */
    private $subscriptionItems;

    /**
     * @param Context $context
     * @param SubscriptionProfileRepository $profileRepository
     * @param StatusManager $statusManager
     * @param ProfileStatus $statusSource
     * @param MessageHistoryLogger $messageHistoryLogger
     * @param Items $subscriptionItems
     */
    public function __construct(
        Context $context,
        SubscriptionProfileRepository $profileRepository,
        StatusManager $statusManager,
        ProfileStatus $statusSource,
        MessageHistoryLogger $messageHistoryLogger,
        Items $subscriptionItems
    ) {
        $this->profileRepository = $profileRepository;
        $this->statusManager = $statusManager;
        $this->statusSource = $statusSource;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->subscriptionItems = $subscriptionItems;
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
            if(!$this->subscriptionItems->canViewSubscriptionById($profileId)){
                throw new \Magento\Framework\Exception\NoSuchEntityException();
            }
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
            if ($this->_request->isAjax()) {
                return $this->getResponse()->representJson('{"error":"true"}');
            }
            return $this->getRedirect();
        }

        if ($this->_request->isAjax()) {
            return $this->getResponse()->representJson('{"error":"false"}');
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
        $redirect = $this->getRequest()->getParam('redirect');
        switch ($redirect) {
            case History::REDIRECT:
                $resultRedirect->setPath('*/subscription/history');
                break;
            case Overview::REDIRECT:
                $resultRedirect->setPath(
                    '*/subscription/edit',
                    ['entity_id' => $this->getRequest()->getParam('entity_id')]
                );
                break;
        }

        return $resultRedirect;
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
