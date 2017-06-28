<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Locale\Format;

class BaseModifier extends AbstractModifier
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CurrencyInterface
     */
    protected $localeCurrency;

    /**
     * @var Format
     */
    private $localeFormat;

    /**
     * BaseModifier constructor.
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return parent::modifyData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return parent::modifyMeta($meta);
    }

    /**
     * Get currency object.
     *
     * @return \Magento\Framework\Currency
     */
    protected function getCurrency()
    {
        $store = $this->storeManager->getStore();
        $currency = $this->getLocaleCurrency()->getCurrency($store->getBaseCurrencyCode());
        return $currency;
    }

    /**
     * The getter function to get the locale currency for real application code
     *
     * @return \Magento\Framework\Locale\CurrencyInterface
     *
     * @deprecated
     */
    protected function getLocaleCurrency()
    {
        if ($this->localeCurrency === null) {
            $this->localeCurrency = \Magento\Framework\App\ObjectManager::getInstance()->get(CurrencyInterface::class);
        }

        return $this->localeCurrency;
    }

    /**
     * The getter function to get the locale format for real application code
     *
     * @return Format
     *
     * @deprecated
     */
    private function getLocaleFormat()
    {
        if ($this->localeFormat === null) {
            $this->localeFormat = \Magento\Framework\App\ObjectManager::getInstance()->get(Format::class);
        }

        return $this->localeFormat;
    }

    /**
     * Get price locale format data.
     *
     * @return string
     */
    protected function getPriceFormatData()
    {
        /** @var \Magento\Framework\Currency $currency */
        $currency = $this->getCurrency();
        $locale = $currency->getLocale();

        /** @var Format $localeFormat */
        $localeFormat = $this->getLocaleFormat();
        /** @var array $priceFormat */
        $priceFormat = $localeFormat->getPriceFormat($locale);
        $priceFormatData = [
            'requiredPrecision' => $priceFormat['precision'],
            'integerRequired' => $priceFormat['integerRequired'],
            'decimalSymbol' => $priceFormat['decimalSymbol'],
            'groupSymbol' => $priceFormat['groupSymbol'],
            'groupLength' => $priceFormat['groupLength']
        ];

        return json_encode($priceFormatData);
    }
}
