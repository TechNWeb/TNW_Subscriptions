<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Class Filesystem - file log
 */
class Filesystem extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/tnw_subscriptions.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var \TNW\Subscriptions\Model\Config
     */
    private $salesforceConfig;

    /**
     * Filesystem constructor.
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystem
     * @param \TNW\Subscriptions\Model\Config $salesforceConfig
     * @param null $filePath
     * @throws \Exception
     */
    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        \TNW\Subscriptions\Model\Config $salesforceConfig,
        $filePath = null
    ) {
        $this->salesforceConfig = $salesforceConfig;
        parent::__construct($filesystem, $filePath);
        $this->setFormatter(new LineFormatter("[%datetime%] [%extra.uid%] %level_name%: %message%\n"));
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->salesforceConfig->getLogStatus()) {
            return;
        }

        parent::write($record);
    }
}
