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

    private $appState;

    public function __construct(
        EmailNotifierFactory $emailNotifierFactory,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $subscriptionProfileFactory,
        TimezoneInterface $timezone,
        ProfileCcUtilsFactory $ccUtilsFactory,
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        State $appState
    ) {
        $this->appState = $appState;
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->ccUtilsFactory = $ccUtilsFactory;
        $this->timezone = $timezone;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->emailNotifierFactory = $emailNotifierFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     *
     */
    public function execute()
    {
        $this->sendRenewalNotifications();
        $this->sendExpiredCardsNotifications();
    }

    /**
     *
     */
    public function sendRenewalNotifications()
    {
        $dayModifier = '+'
            . $this->scopeConfig->getValue(EmailNotifier::XML_PATH_RENEWAL_NOTIFICATION_PERIOD)
            . ' day';
        $orderCollection = $this->getFutureOrderCollection($dayModifier);
        foreach ($orderCollection->getItems() as $item) {
            $this->emailNotifierFactory->create()->renewal(
                $item->getSubscriptionProfileId(),
                $item->getScheduledAt()
            );
        }
    }

    /**
     *
     */
    public function sendExpiredCardsNotifications()
    {
        $dayModifier = '+'
            . $this->scopeConfig->getValue(EmailNotifier::XML_PATH_EXPIRED_CARD_NOTIFICATION_PERIOD)
            . ' day';
        $orderCollection = $this->getFutureOrderCollection($dayModifier);
        foreach ($orderCollection->getItems() as $item) {
            try {
                $profile = $this->subscriptionProfileRepository->getById($item->getSubscriptionProfileId());
            } catch (\Exception $e) {
                $profile = null;
            }
            if ($profile && $this->ccUtilsFactory->create()->isCcExpireBy($profile, $item->getScheduledAt())) {
                $this->emailNotifierFactory->create()->cardExpire($profile);
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
}
