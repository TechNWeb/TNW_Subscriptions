<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var SubscriptionProfileSetupFactory
     */
    private $subscriptionProfileSetupFactory;

    /**
     * @param SubscriptionProfileSetupFactory $subscriptionProfileSetupFactory
     */
    public function __construct(SubscriptionProfileSetupFactory $subscriptionProfileSetupFactory)
    {
        $this->subscriptionProfileSetupFactory = $subscriptionProfileSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), "2.0.3", "<")) {
            /** @var SubscriptionProfileSetup $subscriptionProfileSetup */
            $subscriptionProfileSetup = $this->subscriptionProfileSetupFactory->create(['setup' => $setup]);
            $subscriptionProfileSetup->installEntities();

            $subscriptionProfileSetup->addAttributeGroup(
                'subscription_profile',
                'Default',
                'Additional information'
            );
        }

        $setup->endSetup();
    }
}
