<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model;

use Magento\Framework\Phrase;

/**
 * Messages pool.
 */
class MessagePool
{
    /**
     * @var string[]
     */
    private $messages = [];

    /**
     * Add message.
     *
     * @param string $type
     * @param Phrase|string $message
     * @return $this
     */
    public function addMessage($type, $message)
    {
        if (in_array($type, $this->getMessageTypes())) {
            $this->messages[$type][] = (string)$message;
        }

        return $this;
    }

    /**
     * Return messages by type.
     *
     * @param string $type
     * @return string[]
     */
    public function getMessages($type)
    {
        return isset($this->messages[$type]) ? $this->messages[$type] : [];
    }

    /**
     * Returs possible message types.
     *
     * @return array
     */
    public function getMessageTypes()
    {
        return [
            \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR,
            \Magento\Framework\Message\MessageInterface::TYPE_SUCCESS,
        ];
    }

    /**
     * Pool contains any message or not.
     *
     * @return bool
     */
    public function hasMessages()
    {
        return !empty($this->messages);
    }
}
