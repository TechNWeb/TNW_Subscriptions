<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Cron;

use TNW\Subscriptions\Model\EmailNotifierFactory;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileOrder\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use TNW\Subscriptions\Model\EmailNotifier;
use TNW\Subscriptions\Model\ProfileCcUtilsFactory;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use Magento\Framework\App\State;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment\CollectionFactory as Payment;
use TNW\Subscriptions\Model\Source\ProfileStatus;
use TNW\Subscriptions\Model\Queue\Manager;
use TNW\Subscriptions\Model\Config as SubscriptionConfig;

/**
 * Class NotificationProcessor - used for profile notifications
 */
class NotificationProcessor
{
    /**
     * @var EmailNotifierFactory
     */
    private $emailNotifierFactory;

    /**
     * @var CollectionFactory
     */
    private $subscriptionProfileFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var ProfileCcUtilsFactory
     */
    private $ccUtilsFactory;

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var array
     */
    private $loadedCollections = [];

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Payment
     */
    private $paymentFactory;

    /**
     * @var Manager
     */
    private $queueManager;

    /**
     * @var \TNW\Subscriptions\Cron\ProfileProcessor
     */
    private $processor;

    /**
     * @var SubscriptionConfig
     */
    private $subscriptionConfig;

    /**
     * NotificationProcessor constructor.
     * @param EmailNotifierFactory $emailNotifierFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $subscriptionProfileFactory
     * @param TimezoneInterface $timezone
     * @param ProfileCcUtilsFactory $ccUtilsFactory
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param State $appState
     * @param Payment $paymentFactory
     * @param Manager $queueManager
     * @param \TNW\Subscriptions\Cron\ProfileProcessor $processor
     * @param SubscriptionConfig $subscriptionConfig
     */
    public function __construct(
        EmailNotifierFactory $emailNotifierFactory,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $subscriptionProfileFactory,
        TimezoneInterface $timezone,
        ProfileCcUtilsFactory $ccUtilsFactory,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        State $appState,
        Payment $paymentFactory,
        Manager $queueManager,
        ProfileProcessor $processor,
        SubscriptionConfig $subscriptionConfig
    ) {
        $this->subscriptionConfig = $subscriptionConfig;
        $this->processor = $processor;
        $this->queueManager = $queueManager;
        $this->appState = $appState;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->ccUtilsFactory = $ccUtilsFactory;
        $this->timezone = $timezone;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->emailNotifierFactory = $emailNotifierFactory;
        $this->scopeConfig = $scopeConfig;
        $this->paymentFactory = $paymentFactory;
    }

    /**
     *
     */
    public function execute()
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            //NOTHING TO SET
        }
        if ($this->subscriptionConfig->getIsActiveCronNotifications()) {
            $this->sendRenewalNotifications();
            $this->sendExpiredCardsNotifications();
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendRenewalNotifications()
    {
        $collectionToday = $this->queueManager->getCollectionForDate(
            $this->scopeConfig->getValue(EmailNotifier::XML_PATH_RENEWAL_NOTIFICATION_PERIOD)
        );
        foreach ($this->processor->groupedQueue($collectionToday->getItems()) as $groupQueue) {
            $profileIds = [];
            $scheduledAt = '';
            foreach ($groupQueue as $queue) {
                $profileIds[] = $queue->getData('subscription_profile_id');
                $scheduledAt =  $queue->getData('scheduled_at');
            }
            $this->emailNotifierFactory->create()->renewal(
                $profileIds,
                $scheduledAt
            );
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendExpiredCardsNotifications()
    {
        $orderCollection = $this->getOrderWithCcPayment();
        if ($orderCollection != false) {
            foreach ($orderCollection->getItems() as $item) {
                try {
                    $profile = $this->subscriptionProfileRepository->getById($item->getSubscriptionProfileId());
                } catch (\Exception $e) {
                    $profile = null;
                }
                if (
                    $profile
                    && (
                        $profile->getStatus() == ProfileStatus::STATUS_ACTIVE
                        || $profile->getStatus() == ProfileStatus::STATUS_TRIAL
                    )
                    && $this->ccUtilsFactory->create()->isCcExpireBy($profile, $item->getScheduledAt(), true)
                ) {
                    $this->emailNotifierFactory->create()->cardExpire($profile, $item->getScheduledAt());
                    $profile->getPayment()->setSentMail(1)->save();
                }
            }
        }
    }

    /**
     * Get order with Cc payment method
     *
     * @return \TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\Payment\Collection
     */
    private function getOrderWithCcPayment()
    {
        try {
            return $this->loadedCollections[] = $this->paymentFactory->create()
                ->join(
                    'tnw_subscriptions_subscription_profile_order',
                    'main_table.subscription_profile_id = 
                    tnw_subscriptions_subscription_profile_order.subscription_profile_id AND magento_order_id IS NULL',
                    'scheduled_at'
                )
                ->addFieldToFilter('engine_code', array('nin' => ['checkmo', 'banktransfer', 'purchaseorder']))
                ->addFieldToFilter('payment_additional_info', ['notnull' => true])
                ->addFieldToFilter('sent_mail', 0)
                ->addFieldToSelect('subscription_profile_id');
        } catch (\Exception $e) {
            return false;
        }
    }
}
