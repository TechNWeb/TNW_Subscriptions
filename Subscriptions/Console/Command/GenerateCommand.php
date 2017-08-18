<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
use TNW\Subscriptions\Cron\QuoteCreator;
use TNW\Subscriptions\Model\Config;

/**
 * Command to generate quotes for subscriptions.
 */
class GenerateCommand extends Base
{
    /**
     * Name of process lock file.
     */
    const GENERATE_QUOTES_LOCK_FILE = 'subscription_generate_quotes.lock';

    /**
     * Quote creator
     *
     * @var QuoteCreator.
     */
    private $quoteCreator;

    /**
     * GenerateCommand constructor.
     * @param Filesystem $filesystem
     * @param State $state
     * @param TimezoneInterface $timezone
     * @param Config $config
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param QuoteCreator $quoteCreator
     */
    public function __construct(
        Filesystem $filesystem,
        State $state,
        TimezoneInterface $timezone,
        Config $config,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        QuoteCreator $quoteCreator
    ) {
        $this->quoteCreator = $quoteCreator;
        parent::__construct($filesystem, $state, $timezone, $config, $objectManager, $storeManager);
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('tnw_subscriptions:quotes:generate')
            ->setDescription('Runs quotes generation for subscriptions.');

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
            $output->writeln($this->getDateTime() . ': The process "generate quotes" blocked');
            return Cli::RETURN_SUCCESS;
        }

        try {
            $this->setAreaCode();
            foreach ($this->getStoreManager()->getWebsites() as $website){
                if ($this->getConfig()->isSubscriptionsActive($website->getId())){
                    $this->quoteCreator->process($website->getId());
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
        return self::VAR_LOCKS_DIR . DIRECTORY_SEPARATOR . self::GENERATE_QUOTES_LOCK_FILE;
    }
}