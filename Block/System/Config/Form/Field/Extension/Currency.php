<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\System\Config\Form\Field\Extension;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeCodeResolver;

/**
 * Currency configuration field renderer.
 */
class Currency extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var CurrencyInterface
     */
    private $localeCurrency;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeCodeResolver
     */
    private $scopeCodeResolver;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;

    /**
     * @param PriceCurrencyInterface|null $priceCurrency
     * @param CurrencyInterface|null $localeCurrency
     * @param StoreManagerInterface|null $storeManager
     * @param ScopeCodeResolver|null $scopeCodeResolver
     */
    protected function _construct(
        ?PriceCurrencyInterface $priceCurrency = null,
        ?CurrencyInterface $localeCurrency = null,
        ?StoreManagerInterface $storeManager = null,
        ?ScopeCodeResolver $scopeCodeResolver = null
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->priceCurrency = $objectManager->create(PriceCurrencyInterface::class);
        $this->localeCurrency = $objectManager->create(CurrencyInterface::class);
        $this->storeManager = $objectManager->create(StoreManagerInterface::class);
        $this->scopeCodeResolver = $objectManager->create(ScopeCodeResolver::class);
        $this->pricingHelper = $objectManager->create(\Magento\Framework\Pricing\Helper\Data::class);

        parent::_construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setupElement($element);

        return $element->getElementHtml();
    }

    /**
     * Setup element before render.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Currency_Exception
     */
    protected function setupElement(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setupCurrencySymbol($element);
        $this->setupCurrencyValue($element);
    }

    /**
     * Setup currency symbol.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setupCurrencySymbol(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $currencyCode = $this->getScopeCurrencyCode($element->getScope(), $element->getScopeId());
        $currency = $this->localeCurrency->getCurrency($currencyCode);
        $symbol = $currency->getSymbol() ? $currency->getSymbol() : $currency->getShortName();
        $element->setAddonText($symbol);
    }

    /**
     * Setup currency value according to current currency format
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Currency_Exception
     */
    private function setupCurrencyValue(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $value = $element->getValue();
        // Convert value to float
        // like Magento/Directory/Model/Currency does in method formatTxt
        $price = sprintf("%F", $value);

        // Format currency
        $currencyCode = $this->getScopeCurrencyCode($element->getScope(), $element->getScopeId());
        /** @var \Magento\Framework\Currency $currency */
        $currency = $this->localeCurrency->getCurrency($currencyCode);
        //$format = \Zend_Locale_Data::getContent($currency->getLocale(), 'currencynumber');
        $price = $currency->toCurrency(
            $price,
            [
                'locale' => 'en_US',
                'precision' => 2,
                'symbol' => '',
                'format' => '#0.00',
            ]
        );
        $element->setValue($price);
    }

    /**
     * Get current scope currency code
     *
     * @param $scope
     * @param $scopeId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getScopeCurrencyCode($scope, $scopeId)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $currencyCode = $store->getCurrentCurrencyCode();

        switch ($scope) {
            case 'websites':
                $currencyCode = $this->storeManager->getWebsite($scopeId)->getBaseCurrencyCode();
                break;
            case 'stores':
                $currencyCode = $this->storeManager->getStore($scopeId)->getBaseCurrencyCode();
                break;
        }

        return $currencyCode;
    }
}
