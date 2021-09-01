<?php

namespace TNW\Subscriptions\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfileProfit;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\ProfitCalculator;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;
use TNW\Subscriptions\Model\Queue\Profit;
use TNW\Subscriptions\Model\Queue\ProfitManager;

class TotalProfitPlugin
{
    /**
     * @var SubscriptionProfileProfit
     */
    private $profileProfit;

    /**
     * @var SubscriptionProfile
     */
    private $subscriptionProfile;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var ProfileOrderManager
     */
    private $profileOrderManager;

    /**
     * @var Profit
     */
    private $profit;

    public function __construct(
        SubscriptionProfileProfit $profileProfit,
        SubscriptionProfile $subscriptionProfile,
        Manager $manager,
        ProfileOrderManager $profileOrderManager,
        Profit $profit,
        ProfitManager $profitManager
    ) {
        $this->profileProfit = $profileProfit;
        $this->subscriptionProfile = $subscriptionProfile;
        $this->manager = $manager;
        $this->profileOrderManager = $profileOrderManager;
        $this->profit = $profit;
        $this->profitManager = $profitManager;
    }

    /**
     * @param Order $subject
     * @param $result
     * @param $object
     * @return mixed
     * @throws LocalizedException
     */
    public function afterSave(
        Order $subject,
        $result,
        $object
    ) {
        if ($object->getState() === 'complete') {
            $profileIds = $this->profileOrderManager->getProfileIdsByOrder($object->getEntityId());
            $this->profitManager->setProfilesToCalculateProfit($profileIds);
        }
        return $result;
    }
}
