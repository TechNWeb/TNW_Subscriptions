<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Console\Command;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TNW\Subscriptions\Cron\ProfileProcessor;
use TNW\Subscriptions\Model\Config;

/**
 * Command to create orders for subscriptions.
 */
class ProcessCommand extends Base
{
    /**
     * Name of process lock file.
     */
    const PROCESS_LOCK_FILE = 'subscription_process.lock';

    /**
     * Profile processor.
     *
     * @var ProfileProcessor
     */
    private $profileProcessor;

    /**
     * ProcessCommand constructor.
     * @param Filesystem $filesystem
     * @param State $state
     * @param TimezoneInterface $timezone
     * @param Config $config
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param ProfileProcessor $profileProcessor
     */
    public function __construct(
        Filesystem $filesystem,
        State $state,
        TimezoneInterface $timezone,
        Config $config,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ProfileProcessor $profileProcessor,
        ManagerInterface $eventManager
    ) {
        $this->profileProcessor = $profileProcessor;
        parent::__construct($filesystem, $state, $timezone, $config, $objectManager, $storeManager, $eventManager);
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('tnw_subscriptions:process')
            ->setDescription('Runs order creation for subscriptions.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getConfig()->isSubscriptionsActive()) {
            $output->writeln($this->getDateTime() . ': TNW Subscriptions integration is disabled.');
            return Cli::RETURN_SUCCESS;
        }

        $file = $this->getLockFile();

        try {
            $fileStream = $this->lockProcess($file);
        } catch (FileSystemException $e) {
            $output->writeln($this->getDateTime() . ': The process "process subscriptions" blocked');
            return Cli::RETURN_SUCCESS;
        }

        try {
            $this->setAreaCode();
            foreach ($this->getStoreManager()->getWebsites() as $website){
                if ($this->getConfig()->isSubscriptionsActive($website->getId())){
                    $this->profileProcessor->process($website->getId());
                }
            }
            $this->unlockProcess($fileStream);
        } catch (\Exception $e) {
            $output->writeln($this->getDateTime() . ': ' . $e->getMessage());
            $this->unlockProcess($fileStream);
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected function getLockFile()
    {
        return self::VAR_LOCKS_DIR . DIRECTORY_SEPARATOR . self::PROCESS_LOCK_FILE;
    }
}
