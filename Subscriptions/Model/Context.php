<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Locale\Format;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Locale\CurrencyInterface;
use TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Product\Modal\Context as FormContext;

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
     * Initialized image helper for product.
     *
     * @var
     */
    protected $imageHelper;

    /**
     * Data providers form context.
     *
     * @var FormContext
     */
    protected $formContext;

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
     * @param FormContext $formcontext
     */
    public function __construct(
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        Config $config,
        PriceCurrencyInterface $priceCurrency,
        TimezoneInterface $localeDate,
        Escaper $escaper,
        Format $localeFormat,
        CurrencyInterface $currencyInterface,
        FormContext $formcontext
    ) {
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
        $this->localeDate = $localeDate;
        $this->escaper = $escaper;
        $this->localeFormat = $localeFormat;
        $this->currencyInterface = $currencyInterface;
        $this->formContext = $formcontext;
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
            'groupLength' => $priceFormat['groupLength']
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

    /**
     * Retrieve image helper from quote item.
     *
     * @param $quoteItem
     * @param String $imageId
     * @return ImageHelper|null
     */
    public function getImageHelperForQuoteItem($quoteItem, $imageId = '')
    {
        $imageHelper = $this->formContext->getImageHelper();
        $currentProduct = null;
        switch ($quoteItem->getProductType()) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                $currentProduct = $quoteItem->getProduct();
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $quoteItemChildrens = $quoteItem->getChildren();
                $currentProduct = reset($quoteItemChildrens)->getProduct();
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $quoteItem->getProductType()));
                break;
        }

        $imageHelper = $imageHelper->init($currentProduct, $imageId,
            ['type' => 'small_image', 'width' => '240', 'height' => '240']
        );
        return $imageHelper;
    }

    /**
     * Retrieve image helper from subscription product.
     *
     * @param $item
     * @param String $imageId
     * @return ImageHelper|null
     */
    public function getImageHelperForSubscriptionProduct($item, $imageId = 'category_page_grid')
    {
        $imageHelper = $this->formContext->getImageHelper();
        $currentProduct = null;
        switch ($item->getMagentoProduct()->getTypeId()) {
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                $currentProduct = $item->getMagentoProduct();
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $quoteItemChildrens = $item->getChildren();
                $currentProduct = reset($quoteItemChildrens)->getMagentoProduct();
                break;
            default:
                throw new \InvalidArgumentException(__('Unsupported product type -' . $item->getTypeId()));
                break;
        }

        $imageHelper = $imageHelper->init($currentProduct, $imageId,
            ['type' => 'small_image', 'width' => '240', 'height' => '240']
        );
        return $imageHelper;
    }
}
