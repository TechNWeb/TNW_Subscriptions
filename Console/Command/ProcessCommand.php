<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TNW\Subscriptions\Cron\ProfileProcessorFactory;
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
     * Profile processor Factory.
     *
     * @var ProfileProcessorFactory
     */
    private $profileProcessorFactory;

    /**
     * ProcessCommand constructor.
     * @param Filesystem $filesystem
     * @param State $state
     * @param TimezoneInterface $timezone
     * @param Config $config
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param ProfileProcessorFactory $profileProcessorFactory
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        State $state,
        TimezoneInterface $timezone,
        Config $config,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ProfileProcessorFactory $profileProcessorFactory
    ) {
        $this->profileProcessorFactory = $profileProcessorFactory;
        parent::__construct($filesystem, $state, $timezone, $config, $objectManager, $storeManager);
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
            foreach ($this->getStoreManager()->getWebsites() as $website) {
                if ($this->getConfig()->isSubscriptionsActive($website->getId())) {
                    $this->profileProcessorFactory->create()->process($website->getId());
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
