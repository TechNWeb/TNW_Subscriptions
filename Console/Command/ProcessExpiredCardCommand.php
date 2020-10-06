<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessExpiredCardCommand - cli command class
 */
class ProcessExpiredCardCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \TNW\Subscriptions\Cron\NotificationProcessorFactory
     */
    protected $notificationProcessorFactory;

    /**
     * ProcessRenewalCommand constructor.
     * @param \Magento\Framework\App\State $appState
     * @param \TNW\Subscriptions\Cron\NotificationProcessorFactory $notificationProcessorFactory
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \TNW\Subscriptions\Cron\NotificationProcessorFactory $notificationProcessorFactory
    ) {
        $this->appState = $appState;
        $this->notificationProcessorFactory = $notificationProcessorFactory;
        parent::__construct();
    }

    /**
     * Configure
     */
    protected function configure()
    {
        $this->setName('tnw_subscriptions:notify:expire')
            ->setDescription('Send Card Expiration Notifications');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            $this->notificationProcessorFactory->create()->sendExpiredCardsNotifications();
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        $output->write("\n");
        $output->writeln("<info>Expired Card Notifications Successfully Sent</info>");
    }
}
