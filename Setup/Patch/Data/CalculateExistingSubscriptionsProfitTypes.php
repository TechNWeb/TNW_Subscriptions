<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use TNW\Subscriptions\Model\Queue\Profit;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use Psr\Log\LoggerInterface;

/**
 * Class CalculateExistingSubscriptionsProfitTypes force calculate profit types
 * for existing profile orders.
 */
class CalculateExistingSubscriptionsProfitTypes implements DataPatchInterface, PatchRevertableInterface
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
        $table = $this->moduleDataSetup->getTable('tnw_subscriptions_subscription_profile_entity');

        $selectAllProfiles = $connection->select()
            ->from($table, ['*']);
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

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
