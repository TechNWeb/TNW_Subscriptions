<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Logger\Processor;

use Monolog\LogRecord;

/**
 * Class UidProcessor - uid Porcessor
 */
class UidProcessor
{
    /**
     * @var bool|string
     */
    private $uid;

    /**
     * UidProcessor constructor.
     * @param int $length
     */
    public function __construct($length = 7)
    {
        if (!is_int($length) || $length > 32 || $length < 1) {
            throw new \InvalidArgumentException('The uid length must be an integer between 1 and 32');
        }

        $this->uid = substr(hash('md5', uniqid('', true)), 0, $length);
    }

    public function __invoke(LogRecord $record)
    {
        $recordArray = $record->toArray();
        $recordArray['extra']['uid'] = $this->uid;

        return $record;
    }

    /**
     * @return bool|string
     */
    public function uid()
    {
        return $this->uid;
    }
}
