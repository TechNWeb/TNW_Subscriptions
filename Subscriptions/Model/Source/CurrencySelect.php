<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Backend\Session\Quote as SessionQuote;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Locale\CurrencyInterface;

class CurrencySelect extends AbstractSource
{

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SessionQuote
     */
    private $sessionQuote;

    /**
     * @var StoreManagerInterface
     */
    private $store;

    /**
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * CurrencySelect constructor.
     * @param StoreManagerInterface $storeManager
     * @param SessionQuote $sessionQuote
     * @param CurrencyFactory $currencyFactory
     * @param CurrencyInterface $localeCurrency
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SessionQuote $sessionQuote,
        CurrencyFactory $currencyFactory,
        CurrencyInterface $localeCurrency
    ) {
        $this->storeManager = $storeManager;
        $this->sessionQuote = $sessionQuote;
        $this->currencyFactory = $currencyFactory;
        $this->localeCurrency = $localeCurrency;
    }

    /**
     * Return available currencies for selected store.
     *
     * @return array
     */
    public function getAllOptions()
    {
        $result = [];
        foreach ($this->getAvailableCurrencies() as $code) {
            $result[] = [
                'label' => $this->getCurrencyName($code),
                'value' => $code
            ];
        }

        return $result;
    }


    /**
     * Retrieve currency name by code
     *
     * @param string $code
     * @return string
     */
    public function getCurrencyName($code)
    {
        return $this->localeCurrency->getCurrency($code)->getName();
    }

    /**
     * Retrieve available currency codes
     *
     * @return string[]
     */
    public function getAvailableCurrencies()
    {
        $dirtyCodes = $this->getSelectedStore()->getAvailableCurrencyCodes();
        $codes = [];
        if (is_array($dirtyCodes) && count($dirtyCodes)) {
            /** @var \Magento\Directory\Model\Currency $currency */
            $currency = $this->currencyFactory->create();
            $rates =  $currency->getCurrencyRates(
                $this->storeManager->getStore()->getBaseCurrency(),
                $dirtyCodes
            );
            foreach ($dirtyCodes as $code) {
                if (isset($rates[$code]) || $code == $this->storeManager->getStore()->getBaseCurrencyCode()) {
                    $codes[] = $code;
                }
            }
        }
        return $codes;
    }

    /**
     * Retrieve store model object
     *
     * @return StoreManagerInterface
     */
    public function getSelectedStore()
    {
        if ($this->store === null) {
            $this->store = $this->storeManager->getStore($this->sessionQuote->getStoreId());
            $currencyId = $this->sessionQuote->getCurrencyId();
            if ($currencyId) {
                $this->store->setCurrentCurrencyCode($currencyId);
            }
        }

        return $this->store;
    }

    /**
     * Retrieve current selected currency.
     *
     * @return string
     */
    public function getSelectedCurrencyId()
    {
        $result = "";

        if ($this->store === null) {
            $this->getSelectedStore();
        }

        $currencyId = $this->sessionQuote->getCurrencyId();
        if ($currencyId) {
            $result = $currencyId;
        }

        return $result;
    }
}