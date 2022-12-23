<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Queue\Manager;

/**
 * Interface ExceptionProcessorInterface - defines methods for exception queue processors
 */
interface ExceptionProcessorInterface
{
    /**
     * @param $exception
     * @param $queue
     * @param $quote
     * @param $queueManager
     * @return mixed
     */
    public function process($exception, $queue, $quote, $queueManager);
}
