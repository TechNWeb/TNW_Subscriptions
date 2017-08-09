<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Source;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Subscriptions\Model\Backend\CreateProfile\StepPool;
use TNW\Subscriptions\Model\Backend\Session\Quote as SessionQuote;
use Magento\Directory\Model\Currency;

/**
 * Class CurrencySelect
 */
class CurrencySelect extends AbstractSource
{
    /**
     * Store manager.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Admin session.
     *
     * @var SessionManagerInterface
     */
    private $session;

    /**
     * Current store.
     *
     * @var StoreManagerInterface
     */
    private $store;

    /**
     * Factory for creating Currency.
     *
     * @var CurrencyFactory
     */
    private $currencyFactory;

    /**
     * Provides access to currency config information.
     *
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * CurrencySelect constructor.
     * @param StoreManagerInterface $storeManager
     * @param SessionManagerInterface $session
     * @param CurrencyFactory $currencyFactory
     * @param CurrencyInterface $localeCurrency
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SessionManagerInterface $session,
        CurrencyFactory $currencyFactory,
        CurrencyInterface $localeCurrency
    ) {
        $this->storeManager = $storeManager;
        $this->session = $session;
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
            /** @var Currency $currency */
            $currency = $this->currencyFactory->create();
            $rates = $currency->getCurrencyRates(
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
            /** @var SessionQuote $session */
            $session = $this->session;
            $this->store = $this->storeManager->getStore($session->getStoreId());
            $currencyId = $session->getCurrencyId();
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
        /** @var SessionQuote $session */
        $session = $this->session;
        $currencyId = $session->getCurrencyId();
        if ($currencyId) {
            $result = $currencyId;
        }

        return $result;
    }

    /**
     * Retrieve current data form part name from current step.
     *
     * @param string $currentStep
     * @return string
     */
    public function getCurrentDataFormPartFromStep($currentStep)
    {
        $dataFormPart = '';

        if ($currentStep === StepPool::STEP_PARAM_TYPE_ACCOUNT_INFORMATION
            || $currentStep === StepPool::STEP_PARAM_TYPE_SHIPPING_BILLING) {
            $dataFormPart = 'tnw_subscriptionprofile_create_' . $currentStep . '_form';
        }

        return $dataFormPart;
    }
}