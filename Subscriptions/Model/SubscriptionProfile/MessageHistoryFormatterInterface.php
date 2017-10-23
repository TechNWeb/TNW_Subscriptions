<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Interface for creating subscription quote.
 */
interface MessageHistoryFormatterInterface
{
    public function format(MessageHistory $history);
}
