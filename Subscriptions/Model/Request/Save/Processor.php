<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Request\Save;

use TNW\Subscriptions\Model\SubscriptionProfile\Process\PoolInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ProcessInterface;
use TNW\Subscriptions\Model\Context;

/**
 * Class Processor
 */
class Processor
{
    /**
     * Subscription context.
     *
     * @var Context
     */
    private $context;

    /**
     * @var PoolInterface
     */
    private $requestSaveProcessorsPool;

    /**
     * Processor constructor.
     * @param Context $context
     * @param PoolInterface $requestSaveProcessorsPool
     */
    public function __construct(
        Context $context,
        PoolInterface $requestSaveProcessorsPool
    ) {
        $this->context = $context;
        $this->requestSaveProcessorsPool = $requestSaveProcessorsPool;
    }

    /**
     * Returns save processors pool.
     *
     * @return PoolInterface
     */
    protected function getRequestSaveProcessorsPool()
    {
        return $this->requestSaveProcessorsPool;
    }

    /**
     * Saves request data.
     *
     * @param array $data
     * @return array
     */
    public function processSave($data)
    {
        $messages = [];
        try {
            /** @var ProcessInterface $processor */
            foreach ($this->getRequestSaveProcessorsPool()->getProcessorsInstances() as $processor) {
                $processor->process($data);
                $messages = array_merge($messages, $processor->getErrors());
            }
        } catch (\Exception $e) {
            $this->context->log($e->getMessage());
            $messages = [$e->getMessage()];
        }

        return $messages;
    }
}