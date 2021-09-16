<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use TNW\Subscriptions\Model\Queue\Profit;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use Psr\Log\LoggerInterface;

/**
 * Class CalculateExistingSubscriptionsOrdersProfit forced calculate profit
 * for existing orders.
 */
class CalculateExistingSubscriptionsOrdersProfit implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Profit
     */
    private $profileProfit;

    /**
     * @var SubscriptionProfileRepository
     */
    private $profileRepository;
    
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Profit $profileProfit
     * @param SubscriptionProfileRepository $profileRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Profit $profileProfit,
        SubscriptionProfileRepository $profileRepository,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->profileProfit = $profileProfit;
        $this->profileRepository = $profileRepository;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();

        $selectAllProfiles = $connection->select()
            ->from('tnw_subscriptions_subscription_profile_entity', ['*']);
        $profileIds = $connection->fetchCol($selectAllProfiles);

        foreach ($profileIds as $profileId) {
            try {
                $profile = $this->profileRepository->getById($profileId);
                $this->profileProfit->calculateProfitAndSave($profile);
            } catch (LocalizedException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
