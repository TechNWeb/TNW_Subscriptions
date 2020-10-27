<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * Class Database - db log
 */
class Database extends AbstractProcessingHandler
{
    const MESSAGE_LIMIT_SIZE = 65000;

    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\Message
     */
    protected $resourceMessage;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $systemLogger;

    /**
     * @var \TNW\Subscriptions\Model\Config
     */
    private $subscriptionsConfig;

    /**
     * Database constructor.
     * @param \TNW\Subscriptions\Model\ResourceModel\Message $resourceMessage
     * @param \Psr\Log\LoggerInterface $systemLogger
     * @param \TNW\Subscriptions\Model\Config $subscriptionsConfig
     */
    public function __construct(
        \TNW\Subscriptions\Model\ResourceModel\Message $resourceMessage,
        \Psr\Log\LoggerInterface $systemLogger,
        \TNW\Subscriptions\Model\Config $subscriptionsConfig
    ) {
        $this->resourceMessage = $resourceMessage;
        $this->systemLogger = $systemLogger;
        $this->subscriptionsConfig = $subscriptionsConfig;

        parent::__construct();
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->subscriptionsConfig->getDbLogStatus()) {
            return;
        }

        try {
            do {
                $this->resourceMessage
                    ->saveRecord(
                        $record['extra']['uid'],
                        $record['level'],
                        substr($record['message'], 0, self::MESSAGE_LIMIT_SIZE)
                    );

                $record['message'] = substr($record['message'], self::MESSAGE_LIMIT_SIZE);
            } while (!empty($record['message']));

        } catch (\Exception $e) {
            $this->systemLogger->error($e);
        }
    }
}
