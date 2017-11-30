<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Processor;

use TNW\Subscriptions\Model\QuoteSessionInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\PoolInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\Process\ProcessInterface;
use TNW\Subscriptions\Model\Context;

/**
 * Class Processor
 */
class Request
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
    private $requestSaveProcessorsPool;

    /**
     * @var QuoteSessionInterface
     */
    private $session;

    /**
     * @param Context $context
     * @param PoolInterface $requestSaveProcessorsPool
     * @param QuoteSessionInterface $session
     */
    public function __construct(
        Context $context,
        PoolInterface $requestSaveProcessorsPool,
        QuoteSessionInterface $session
    ) {
        $this->context = $context;
        $this->requestSaveProcessorsPool = $requestSaveProcessorsPool;
        $this->session = $session;
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
    public function processSave(array $data)
    {
        $messages = [];
        try {
            $instances = $this->getRequestSaveProcessorsPool()->getProcessorsInstances();
            /** @var ProcessInterface $processor */
            foreach ($instances as $processor) {
                $processor->process($data);
                $messages = array_merge($messages, $processor->getErrors());
            }
        } catch (\Exception $e) {
            $this->context->log($e->getMessage());
            $messages = [$e->getMessage()];
        }

        // Add session messages
        $additionalMessages = $this->session->getErrors(true);
        if (!empty($additionalMessages)) {
            $messages = array_merge($messages, $additionalMessages);
        }

        return $messages;
    }
}
