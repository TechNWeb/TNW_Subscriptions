<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessExpiredCardCommand - send expired card notifications
 */
class ProcessExpiredCardCommand extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \TNW\Subscriptions\Cron\NotificationProcessor
     */
    protected $notificationProcessor;

    /**
     * ProcessRenewalCommand constructor.
     * @param \Magento\Framework\App\State $appState
     * @param \TNW\Subscriptions\Cron\NotificationProcessor $notificationProcessor
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \TNW\Subscriptions\Cron\NotificationProcessor $notificationProcessor
    ) {
        $this->appState = $appState;
        $this->notificationProcessor = $notificationProcessor;
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
            $this->notificationProcessor->sendExpiredCardsNotifications();
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
        $output->write("\n");
        $output->writeln("<info>Expired Card Notifications Successfully Sent</info>");
    }
}