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
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

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
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    public function __construct(
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        Config $config,
        PriceCurrencyInterface $priceCurrency,
        TimezoneInterface $localeDate
    ) {

        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
        $this->localeDate = $localeDate;
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

    /**
     * @return PriceCurrencyInterface
     */
    public function getPriceCurrency()
    {
        return $this->priceCurrency;
    }

    /**
     * @return TimezoneInterface
     */
    public function getLocaleDate()
    {
        return $this->localeDate;
    }
}