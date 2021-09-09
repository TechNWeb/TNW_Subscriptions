<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
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

/**
 * Class TotalProfitPlugin sets profile ids to calculate profit
 * after order is invoiced
 */
class TotalProfitPlugin
{
    /**
     * @var ProfileOrderManager
     */
    private $profileOrderManager;

    /**
     * @var ProfitManager
     */
    private $profitManager;

    public function __construct(
        ProfileOrderManager $profileOrderManager,
        ProfitManager $profitManager
    ) {
        $this->profileOrderManager = $profileOrderManager;
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
        if ($object->hasInvoices()) {
            $profileIds = $this->profileOrderManager->getProfileIdsByOrder($object->getEntityId());
            $this->profitManager->setProfilesToCalculateProfit($profileIds);
        }
        return $result;
    }
}
