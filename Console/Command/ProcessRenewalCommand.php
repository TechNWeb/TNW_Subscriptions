<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessRenewalCommand- cli command class for renewal processing
 */
class ProcessRenewalCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \TNW\Subscriptions\Cron\NotificationProcessor
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
        $this->setName('tnw_subscriptions:notify:renewal')
            ->setDescription('Send Renewal Notifications');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            $this->notificationProcessorFactory->create()->sendRenewalNotifications();
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        $output->write("\n");
        $output->writeln("<info>Renewal Notifications Successfully Sent</info>");
        return Cli::RETURN_SUCCESS;
    }
}
