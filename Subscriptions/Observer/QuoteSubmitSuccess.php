<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class QuoteSubmitSuccess implements ObserverInterface
{
    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\Manager
     */
    private $profileManager;

    /**
     * @var \TNW\Subscriptions\Model\Quote\ItemGroup
     */
    private $quoteItemGroup;

    /**
     * @var \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger
     */
    private $messageHistoryLogger;

    /**
     * @var \TNW\Subscriptions\Model\Queue\Manager
     */
    private $queueManager;

    /**
     * @var \TNW\Subscriptions\Model\Source\ProfileStatus
     */
    private $profileStatus;

    public function __construct(
        \TNW\Subscriptions\Model\SubscriptionProfile\Manager $profileManager,
        \TNW\Subscriptions\Model\Quote\ItemGroup $quoteItemGroup,
        \TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger $messageHistoryLogger,
        \TNW\Subscriptions\Model\Queue\Manager $queueManager,
        \TNW\Subscriptions\Model\Source\ProfileStatus $profileStatus
    ) {
        $this->profileManager = $profileManager;
        $this->quoteItemGroup = $quoteItemGroup;
        $this->messageHistoryLogger = $messageHistoryLogger;
        $this->queueManager = $queueManager;
        $this->profileStatus = $profileStatus;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getData('quote');
        if (!$quote instanceof \Magento\Quote\Model\Quote || !$quote->getData('is_tnw_subscription')) {
            return;
        }

        $order = $observer->getData('order');
        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        foreach ($this->quoteItemGroup->groups($quote->getAllVisibleItems()) as $groupKey => $quoteItems) {
            if (strcasecmp($groupKey, 'no_option') === 0) {
                continue;
            }

            $this->profileManager
                ->reset()
                ->populateProfileData($quote, $quoteItems)
                ->populatePaymentData($quote->getPayment());

            $profile = $this->profileManager->getProfile();
            $oldStatus = $profile->getStatus();

            $status = $this->profileStatus::STATUS_ACTIVE;
            if ($profile->getTrialStartDate()) {
                $startDate = $profile->getStartDate();
                if (\date_create()->diff(\date_create($startDate))->invert === 0) {
                    $status = $this->profileStatus::STATUS_TRIAL;
                }
            }

            $profile->setStatus($status);

            // Save profile
            $this->profileManager->saveProfile();

            // Add comment about profile creation.
            $this->messageHistoryLogger->message(
                $this->messageHistoryLogger::MESSAGE_SUBSCRIPTION_CREATED,
                [
                    $profile->getLabel()
                ],
                $profile->getId()
            );

            //Add comment profile place.
            $this->messageHistoryLogger->message(
                $this->messageHistoryLogger::MESSAGE_SUBSCRIPTION_STATUS_CHANGED,
                [
                    $this->profileStatus->getLabelByValue($oldStatus),
                    $this->profileStatus->getLabelByValue($status)
                ],
                $profile->getId()
            );

            // Add comment profile place.
            $this->messageHistoryLogger->message(
                $this->messageHistoryLogger::MESSAGE_ORDER_CREATED_FROM_QUOTE,
                [
                    $order->getIncrementId(),
                    $this->messageHistoryLogger->getConvertedQuoteId($quote->getId())
                ],
                $profile->getId()
            );

            $startDate = $profile->getTrialStartDate() ?: $profile->getStartDate();
            //Assign quote to new profile
            $relation = $this->profileManager->assignQuoteToProfile($quote, $profile, $startDate);
            //Add new relation to profile processing queue in "pending" state.
            $queueItemIds = $this->queueManager->insertItems([$relation->getId()]);
            $this->queueManager->makeRunning($queueItemIds);

            $this->profileManager->assignOrderToProfile($relation, $order);
            $this->queueManager->makeCompleted($queueItemIds);
        }
    }
}
