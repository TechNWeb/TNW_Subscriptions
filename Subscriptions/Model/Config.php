<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;

    function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }
}