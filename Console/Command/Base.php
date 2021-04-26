<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Console\Command;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TNW\Subscriptions\Model\Config;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\ObjectManager\ConfigLoaderInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Base class for subscription commands.
 */
abstract class Base extends Command
{
    const VAR_LOCKS_DIR = 'locks';

    /**
     * @var WriteInterface
     */
    private $dir;

    /**
     * @var State
     */
    private $state;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var Config
     */
    private $config;

    /**
     * Object manager.
     *
     * @var  ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Store manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * Base constructor.
     * @param Filesystem $filesystem
     * @param State $state
     * @param TimezoneInterface $timezone
     * @param Config $config
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Filesystem $filesystem,
        State $state,
        TimezoneInterface $timezone,
        Config $config,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ManagerInterface $eventManager
    ) {
        $this->dir = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->state = $state;
        $this->filesystem = $filesystem;
        $this->timezone = $timezone;
        $this->config = $config;
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;

        parent::__construct();
    }

    /**
     * Returns config model.
     *
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns store manager.
     *
     * @return StoreManagerInterface
     */
    protected function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Returns lock file.
     *
     * @return string
     */
    abstract protected function getLockFile();

    /**
     * Creates lock file for process.
     *
     * @param $file
     * @return Filesystem\File\WriteInterface
     */
    protected function lockProcess($file)
    {
        $fileStream = $this->dir->openFile($file, 'w+');
        $fileStream->lock(LOCK_EX | LOCK_NB);
        return $fileStream;
    }

    /**
     * Deletes lock file for process.
     *
     * @param \Magento\Framework\Filesystem\File\WriteInterface $fileStream
     */
    protected function unlockProcess($fileStream)
    {
        $fileStream->unlock();
        $fileStream->close();
    }

    /**
     * Sets area code and configure object manager.
     */
    protected function setAreaCode()
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $configLoader = $this->objectManager->get(ConfigLoaderInterface::class);
        $this->objectManager->configure($configLoader->load(Area::AREA_ADMINHTML));
    }

    /**
     * Returns current time.
     *
     * @return string
     */
    protected function getDateTime()
    {
        return $this->timezone->date()->format('m/d/y H:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $result = parent::run($input, $output);
        $this->eventManager->dispatch('tnw_salesforce_entities_sync');

        return $result;
    }
}
