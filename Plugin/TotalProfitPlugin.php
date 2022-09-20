<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as ProfileOrderManager;
use TNW\Subscriptions\Model\Queue\ProfitManager;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;

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

    /**
     * @var Manager
     */
    private $manager;

    /**
     * TotalProfitPlugin constructor.
     * @param ProfileOrderManager $profileOrderManager
     * @param ProfitManager $profitManager
     * @param Manager $manager
     */
    public function __construct(
        ProfileOrderManager $profileOrderManager,
        ProfitManager $profitManager,
        Manager $manager
    ) {
        $this->profileOrderManager = $profileOrderManager;
        $this->profitManager = $profitManager;
        $this->manager = $manager;
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
        $this->manager->setProfilesToCalculateProfit($object);
        return $result;
    }
}
