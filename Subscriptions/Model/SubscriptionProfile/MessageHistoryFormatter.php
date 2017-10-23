<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
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
     * @var string
     */
    private $urlPath;

    /**
     * MessageHistoryFomatter constructor.
     * @param UrlInterface $url
     * @param string $urlPath
     */
    public function __construct(
        UrlInterface $url,
        $urlPath = 'catalog/product/edit'
    ) {
        $this->url = $url;
        $this->urlPath = $urlPath;
    }

    /**
     * Format message.
     * Add product link to the message.
     *
     * @param MessageHistory $history
     * @return string
     */
    public function format(MessageHistory $history)
    {
        return preg_replace_callback(
            '/\{productUrl\|(\d+)\}/i',
            function ($matches) {
                return $this->url->getUrl($this->urlPath, ['id' => $matches[1]]);
            },
            $history->getMessage()
        );
    }
}
