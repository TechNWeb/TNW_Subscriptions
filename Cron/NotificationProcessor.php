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
use TNW\Subscriptions\Cron\ProfileProcessor;

/**
 * Class NotificationProcessor
 * @package TNW\Subscriptions\Cron
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

    private $queueManager;

    private $processor;

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
        ProfileProcessor $processor
    ) {
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
        $this->sendRenewalNotifications();
        $this->sendExpiredCardsNotifications();
    }

    /**
     *
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
     *
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
     * @param $dayModifier
     * @return mixed
     */
    private function getFutureOrderCollection($dayModifier)
    {
        if (!$dayModifier || !isset($this->loadedCollections[$dayModifier])) {
            $currentDate = $this->timezone->date();
            if ($dayModifier) {
                $currentDate->modify($dayModifier);
            }
            $this->loadedCollections[$dayModifier] = $this->subscriptionProfileFactory->create()
                ->addFieldToFilter('scheduled_at', [
                    'date' => true,
                    'from' => $currentDate->format('Y-m-d 00:00:00'),
                    'to' => $currentDate->format('Y-m-d 23:59:59')
                ])
                ->addFieldToFilter('magento_order_id', ['null' => true])
                ->addFieldToSelect('subscription_profile_id')
                ->addFieldToSelect('scheduled_at')
            ;
        }
        return $this->loadedCollections[$dayModifier];
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
                ->addFieldToFilter('engine_code', array('neq' => 'checkmo'))
                ->addFieldToFilter('payment_additional_info', ['notnull' => true])
                ->addFieldToFilter('sent_mail', 0)
                ->addFieldToSelect('subscription_profile_id');
        } catch (\Exception $e) {
            return false;
        }
    }
}
