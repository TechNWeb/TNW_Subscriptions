<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Processor;

use TNW\Subscriptions\Model\SubscriptionProfile\Process\PoolInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ResponseInterface;
use TNW\Subscriptions\Model\Context;

/**
 * Response Processor class.
 */
class Response
{
    /**
     * Subscription context.
     *
     * @var Context
     */
    protected $context;

    /**
     * @var PoolInterface
     */
    private $responseProcessorsPool;

    /**
     * Processor constructor.
     * @param Context $context
     * @param PoolInterface $requestSaveProcessorsPool
     */
    public function __construct(
        Context $context,
        PoolInterface $responseProcessorsPool
    ) {
        $this->context = $context;
        $this->responseProcessorsPool = $responseProcessorsPool;
    }

    /**
     * Returns save processors pool.
     *
     * @return PoolInterface
     */
    protected function getResponseProcessorsPool()
    {
        return $this->responseProcessorsPool;
    }

    /**
     * Saves request data.
     *
     * @param array $data
     * @return array
     */
    public function processResponse(array $data)
    {
        $response = [];
        try {
            /** @var ResponseInterface $processor */
            foreach ($this->getResponseProcessorsPool()->getProcessorsInstances() as $processor) {
                $response = array_merge($response, $processor->process($data));
            }
        } catch (\Exception $e) {
            $this->context->log($e->getMessage());
            $response = [$e->getMessage()];
        }

        return $response;
    }
}
