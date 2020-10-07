<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\UrlInterface;

/**
 * Format history message to show in 'Change History' block
 */
class MessageHistoryFormatter implements MessageHistoryFormatterInterface
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var array
     */
    private $matches;

    /**
     * MessageHistoryFomatter constructor.
     * @param UrlInterface $url
     * @param array $matches
     */
    public function __construct(
        UrlInterface $url,
        array $matches = []
    ) {
        $this->url = $url;
        $this->matches = $matches;
    }

    /**
     * @inheritdoc
     */
    public function format(MessageHistory $history)
    {
        $massage = $history->getMessage();
        foreach ($this->matches as $match => $urlPath) {
            $massage = preg_replace_callback(
                '/\{'.preg_quote($match, '/').'\|(\d+)\}/i',
                function ($matches) use ($urlPath) {
                    return $this->url->getUrl(sprintf($urlPath, $matches[1]));
                },
                $massage
            );
        }

        return $massage;
    }
}
