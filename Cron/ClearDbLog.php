<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Cron;

/**
 * Class ClearDbLog - cron
 */
class ClearDbLog
{
    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\Message
     */
    private $logResource;

    /**
     * UpdateCurrencyRates constructor.
     *
     * @param \TNW\Subscriptions\Model\ResourceModel\Message $logResource
     */
    public function __construct(
        \TNW\Subscriptions\Model\ResourceModel\Message $logResource
    ) {
        $this->logResource = $logResource;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->logResource->clearLast();
    }
}
