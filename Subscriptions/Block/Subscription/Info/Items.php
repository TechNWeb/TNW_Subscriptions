<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Info;

use TNW\Subscriptions\Block\Subscription\Info\Messages\ExpireWarningSupportInterface;
use TNW\Subscriptions\Model\MessagePool;

/**
 * Subscription items block on customer account dashboard.
 */
class Items extends ContentAbstract implements ExpireWarningSupportInterface
{
    /**
     * @var string
     */
    protected $_template = 'subscription/items.phtml';

    /**
     * @return MessagePool
     */
    public function getMessagePool()
    {
        return $this->messagePool;
    }

    /**
     * @return bool
     */
    public function isSupported()
    {
        return true;
    }
}
