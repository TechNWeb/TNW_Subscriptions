<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Engine;

use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Context;

abstract class Base implements EngineInterface
{
    /** @var Config */
    protected $config;
    /** @var Context */
    protected $context;

    public function __construct(
        Config $config,
        Context $context
    ) {
        $this->config = $config;
        $this->context = $context;
    }
}