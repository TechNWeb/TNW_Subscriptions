<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Model;

use Magento\Framework\Locale\Format;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Locale\CurrencyInterface;

/**
 * Class subscription context.
 *
 * @method messageError($format, $args = null, $_ = null)
 * @method messageSuccess($format, $args = null, $_ = null)
 * @method messageWarning($format, $args = null, $_ = null)
 * @method messageNotice($format, $args = null, $_ = null)
 * @method messageDebug($format, $args = null, $_ = null)
 */
class Context
{
    /**
     * @var ManagerInterface
     * @deprecated
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
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var Format
     */
    private $localeFormat;

    /**
     * @var CurrencyInterface
     */
    private $currencyInterface;

    /**
     * Context constructor.
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param Config $config
     * @param PriceCurrencyInterface $priceCurrency
     * @param TimezoneInterface $localeDate
     * @param Escaper $escaper
     * @param Format $localeFormat
     * @param CurrencyInterface $currencyInterface
     */
    public function __construct(
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        Config $config,
        PriceCurrencyInterface $priceCurrency,
        TimezoneInterface $localeDate,
        Escaper $escaper,
        Format $localeFormat,
        CurrencyInterface $currencyInterface
    ) {
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
        $this->localeDate = $localeDate;
        $this->escaper = $escaper;
        $this->localeFormat = $localeFormat;
        $this->currencyInterface = $currencyInterface;
    }

    /**
     * @return ManagerInterface
     * @deprecated
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

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (\stripos($name, 'message') !== 0) {
            throw new \BadMethodCallException('Unknown method');
        }

        if (\count($arguments) === 0) {
            throw new \BadMethodCallException('Missed argument "$format"');
        }

        // Prepare arguments
        $arguments = array_map(function ($argument) {
            return \print_r($argument, true);
        }, array_map([$this, 'convertToArray'], $arguments));

        // FIX: Too few argument
        if (\substr_count($arguments[0], '%') > (\count($arguments) - 1)) {
            $arguments[0] = \str_replace('%', '%%', $arguments[0]);
        }

        /** @var string $message */
        $message = sprintf(...$arguments);

        /** switch level */
        switch (strtolower(substr($name, 7))) {
            case 'error':
                $this->logger->error($message);
                break;
            case 'success':
                $this->logger->info($message);
                break;
            case 'warning':
                $this->logger->warning($message);
                break;
            case 'notice':
                $this->logger->notice($message);
                break;
            case 'debug':
                $this->logger->debug($message);
                break;
        }
    }

    /**
     * @param $entity
     * @return mixed
     */
    private function convertToArray($entity)
    {
        static $level = 0;

        // Up level
        if (++$level > $this->config->messageObjectDeep()) {
            $entity = '[... nesting level exceeded ...]';
        }

        if ($entity instanceof \Magento\Framework\Phrase) {
            $entity = $entity->render();
        }

        if ($entity instanceof \Exception) {
            $entity = $entity->getMessage();
        }

        if ($entity instanceof \Magento\Framework\Data\Collection) {
            $entity = array_filter($entity->getItems(), function ($item) {
                if ($item instanceof \Magento\Framework\Model\AbstractModel) {
                    return !$item->isDeleted();
                }

                return true;
            });
        }

        if ($entity instanceof \Magento\Framework\Model\AbstractModel) {
            $entity = $entity->getData();
        }

        if (\is_object($entity)) {
            $entity = \sprintf('[... unsupported object name: %s ...]', \get_class($entity));
        }

        if (\is_array($entity)) {
            $entity = \array_map([$this, 'convertToArray'], $entity);
        }

        if (\is_bool($entity)) {
            $entity =  $entity ? 'true' : 'false';
        }

        if (\is_scalar($entity)) {
            $entity = (string) $entity;
        }

        // Down level
        $level--;
        return $entity;
    }

    /**
     * @param string $message
     * @param string $messageType
     * @param string $group
     * @return $this
     * @deprecated
     */
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

    /**
     * @param $text
     * @param string $logLevel
     * @deprecated
     */
    public function log($text, $logLevel = LogLevel::INFO)
    {
        $this->logger->log($logLevel, $text);
    }

    public function throwException($message)
    {
        $message = is_string($message) ? __($message) : $message;
        throw new \Magento\Framework\Exception\LocalizedException($message);
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

    /**
     * @return Escaper
     */
    public function getEscaper()
    {
        return $this->escaper;
    }

    /**
     * Get price locale format data.
     *
     * @return string
     */
    public function getPriceFormatData($currencyCode)
    {
        /** @var \Magento\Framework\Currency $currency */
        $currency = $this->currencyInterface->getCurrency($currencyCode);
        $locale = $currency->getLocale();

        /** @var array $priceFormat */
        $priceFormat = $this->localeFormat->getPriceFormat($locale);
        $priceFormatData = [
            'requiredPrecision' => $priceFormat['precision'],
            'integerRequired' => $priceFormat['integerRequired'],
            'decimalSymbol' => $priceFormat['decimalSymbol'],
            'groupSymbol' => $priceFormat['groupSymbol'],
            'groupLength' => $priceFormat['groupLength'],
            'pattern' => $priceFormat['pattern'],
        ];

        return json_encode($priceFormatData);
    }

    /**
     * Inserts element before element in array.
     *
     * @param array $result - array to insert.
     * @param $beforeValue - value of the element before which it will be inserted
     * @param array $element - element to insert.
     * @return array
     */
    public function arrayInsertBefore(array $result, $beforeValue, $element)
    {
        $pos = array_search($beforeValue, array_keys($result));
        $result = array_merge(
            array_slice($result, 0, $pos),
            $element,
            array_slice($result, $pos)
        );
        return $result;
    }

    /**
     * Checks if value is in json format.
     *
     * @param mixed $value
     * @return bool
     */
    public function isJson($value)
    {
        if ($value === '') {
            return false;
        }

        \json_decode($value);
        if (\json_last_error()) {
            return false;
        }

        return true;
    }
}
