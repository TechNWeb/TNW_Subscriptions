<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Source;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote as ModelQuote;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Item;
use Magento\Tax\Helper\Data;
use TNW\Subscriptions\Model\Context;

/**
 * Class ShippingMethods
 */
class ShippingMethods
{
    const UNAVAILABLE_PRODUCT_TYPES = [
        \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
        \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
    ];

    /**
     * @var ModelQuote
     */
    private $quote;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Data
     */
    private $taxHelper;

    /**
     * Available shipping codes when shipping methods don't cost depends on the products.
     *
     * @var array
     */
    private $dontCostDependedMethodsCodes = [
        'flatrate',
        'freeshipping',
    ];

    /**
     * ShippingMethods constructor.
     * @param Context $context
     * @param Data $taxHelper
     */
    public function __construct(
        Context $context,
        Data $taxHelper
    ) {
        $this->context = $context;
        $this->taxHelper = $taxHelper;
    }

    /**
     * @return ModelQuote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param ModelQuote $quote
     */
    public function setQuote(ModelQuote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * Returns list of shipping methods for quote as array.
     *
     * @return array
     */
    public function getShippingMethodsAsOptionArray()
    {
        $result = [];

        $methods = $this->getCurrentRates();

        foreach ($methods as $code => $rates) {
            /** @var Rate $rate */
            foreach ($rates as $rate) {
                $result[] = [
                    'value' => $rate->getCode(),
                    'label' => $this->getMethodLabel($code, $rate)
                ];
            }
        }

        return $result;
    }

    /**
     * Returns rates list for shipping method.
     *
     * @return array
     */
    private function getCurrentRates()
    {
        return $this->getQuote()->getShippingAddress()->getGroupedAllShippingRates();
    }

    /**
     * Returns label for shipping method from quote.
     *
     * @return string
     */
    public function getCurrentMethodLabel()
    {
        $result = '';

        $shippingMethod = $this->getQuote()->getShippingAddress()->getShippingMethod();

        foreach ($this->getCurrentRates() as $code => $group) {
            /** @var Rate $rate */
            foreach ($group as $rate) {
                if ($rate->getCode() === $shippingMethod) {
                    $result = $this->getMethodLabel($code, $rate);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Returns full shipping method label.
     *
     * @param string $code
     * @param Rate $rate
     * @return string
     */
    private function getMethodLabel($code, $rate)
    {
        $result = '';

        $result .= $this->getCarrierTitle($code);

        $result .= ' (' . $this->getMethodTitle($rate) . ')';

        $cost = $this->getShippingPrice($rate->getPrice(), $this->taxHelper->displayShippingPriceIncludingTax());
        $costInclTax = $this->getShippingPrice($rate->getPrice(), true);

        $result .= ' - ' . $cost;

        if ($costInclTax !== $cost && $this->taxHelper->displayShippingBothPrices()) {
            $result .= ' (' . __('Incl. Tax') . $costInclTax . ')';
        }

        return $result;
    }

    /**
     * Returns method config title.
     *
     * @param string $code
     * @return mixed|null|string
     */
    private function getCarrierTitle($code)
    {
        $carrierTitle = $this->context->getConfig()->getStoreConfig(
            'carriers/' . $code . '/title',
            null,
            $this->getQuote()->getStoreId()
        );

        return $carrierTitle;
    }

    /**
     * Returns shipping method label.
     *
     * @param Rate $rate
     * @return array|string
     */
    private function getMethodTitle($rate)
    {
        return $this->context->getEscaper()->escapeHtml(
            $rate->getMethodTitle() ?: $rate->getMethodDescription()
        );
    }

    /**
     * Get shipping price.
     *
     * @param float $price
     * @param bool $flag
     * @return float
     */
    public function getShippingPrice($price, $flag)
    {
        return $this->context->getPriceCurrency()->convertAndFormat(
            $this->taxHelper->getShippingPrice(
                $price,
                $flag,
                $this->getQuote()->getShippingAddress(),
                null,
                $this->getQuote()->getStore()
            ),
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->getQuote()->getStore()
        );
    }

    /**
     * Do not show shipping method label for virtual/downloadable products
     *
     * @return bool
     */
    public function canShowShippingMethodLabel()
    {
        $result = false;
        $quoteItems = $this->quote->getAllItems();
        /** @var Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            if (!in_array($quoteItem->getProductType(), $this::UNAVAILABLE_PRODUCT_TYPES, true)) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Return list of shipping codes when shipping methods don't cost depends on the products.
     *
     * @return array
     */
    public function getDontCostDependedMethodsCodes()
    {
        return $this->dontCostDependedMethodsCodes;
    }

    /**
     * Return attention message.
     *
     * @return string
     */
    public function getShippingAttentionMessage()
    {
        return __('the shipping fee is subject to change for each shipment');
    }

    /**
     * Return shipping method code.
     *
     * @return string
     */
    public function getCurrentShippingMethod()
    {
        return $this->getQuote()->getShippingAddress()->getShippingMethod();
    }

}
