<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\CurrencyInterface;
use TNW\Subscriptions\Model\Context;

/**
 * Base class for product edit page modifiers.
 */
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
     * @var Context
     */
    protected $context;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context
    ) {
        $this->storeManager = $storeManager;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
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
     * Get price locale format data.
     *
     * @return string
     */
    protected function getPriceFormatData()
    {
        $store = $this->storeManager->getStore();
        $priceFormatData = $this->context->getPriceFormatData($store->getBaseCurrencyCode());

        return $priceFormatData;
    }
}
