<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Interface for Subscription profile formatting messages.
 */
interface MessageHistoryFormatterInterface
{
    /**
     * Format message.
     * Add product link to the message.
     *
     * @param MessageHistory $history
     * @return string
     */
    public function format(MessageHistory $history);
}
