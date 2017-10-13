<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Info\Messages;

use TNW\Subscriptions\Model\MessagePool;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Support warning about Cc expiration.
 */
interface ExpireWarningSupportInterface
{
    /**
     * @return MessagePool
     */
    function getMessagePool();

    /**
     * @return SubscriptionProfile
     */
    function getSubscriptionProfile();

    /**
     * @return bool
     */
    function isSupported();
}
