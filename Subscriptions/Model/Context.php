<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model;


use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Context
{

    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        Config $config
    ) {

        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return ManagerInterface
     */
    public function getMessageManager()
    {
        return $this->messageManager;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function addMessage($message, $messageType, $group)
    {
        $this->messageManager->addMessage(
            $this->messageManager
                ->createMessage($messageType)
                ->setText($message),
            $group
        );

        return $this;
    }

    public function log($text, $logLevel = LogLevel::INFO)
    {
        $this->logger->log($logLevel, $text);
    }

    public function throwException($message)
    {
        throw new \Exception($message);
    }
}