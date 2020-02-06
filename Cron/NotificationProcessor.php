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
     * NotificationProcessor constructor.
     * @param EmailNotifierFactory $emailNotifierFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $subscriptionProfileFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        EmailNotifierFactory $emailNotifierFactory,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $subscriptionProfileFactory,
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
        $this->subscriptionProfileFactory = $subscriptionProfileFactory;
        $this->emailNotifierFactory = $emailNotifierFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $this->sendRenewalNotifications();
    }

    public function sendRenewalNotifications()
    {
        $dayModifier = '+'
            . $this->scopeConfig->getValue(EmailNotifier::XML_PATH_RENEWAL_NOTIFICATION_PERIOD)
            . ' day';
        $currentDate = $this->timezone->date()->modify($dayModifier);
        $orderCollection = $this->subscriptionProfileFactory->create()
            ->addFieldToFilter('scheduled_at', [
                'date' => true,
                'from' => $currentDate->format('Y-m-d 00:00:00'),
                'to' => $currentDate->format('Y-m-d 23:59:59')
            ])
            ->addFieldToFilter('magento_order_id', ['null' => true])
            ->addFieldToSelect('subscription_profile_id')
            ->addFieldToSelect('scheduled_at')
        ;
        foreach ($orderCollection->getItems() as $item) {
            $this->emailNotifierFactory->create()->renewal(
                $item->getSubscriptionProfileId(),
                $item->getScheduledAt()
            );
        }
    }
}
