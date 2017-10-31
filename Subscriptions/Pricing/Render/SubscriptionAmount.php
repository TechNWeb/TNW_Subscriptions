<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Pricing\Render;

use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\Pricing\Render\Amount as BaseAmount;

/**
 * Subscription price amount renderer
 */
class SubscriptionAmount extends BaseAmount
{
    /**
     * Subscription Amount constructor.
     *
     * @param Template\Context $context
     * @param AmountInterface $amount
     * @param PriceCurrencyInterface $priceCurrency
     * @param RendererPool $rendererPool
     * @param SaleableInterface|null $saleableItem
     * @param PriceInterface|null $price
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AmountInterface $amount,
        PriceCurrencyInterface $priceCurrency,
        RendererPool $rendererPool,
        SaleableInterface $saleableItem = null,
        PriceInterface $price = null,
        array $data = []
    ) {
        parent::__construct($context, $amount, $priceCurrency, $rendererPool, $saleableItem, $price, $data);
    }

    /**
     * Retrieve top|bottom message to subscription price.
     *
     * @param string $type
     * @return string
     */
    public function getMessage(string $type)
    {
        $message = '';
        if ($type) {
            $defaultData = $this->getPriceData();
            $message = isset($defaultData[$type . '_message']) ? $defaultData[$type . '_message'] : '';
        }
        return $message;
    }

    /**
     * Retrieve price.
     *
     * @return string
     */
    public function getPrice()
    {
        $defaultData = $this->getPriceData();
        return isset($defaultData['price']) ? $defaultData['price'] : '';
    }

    /**
     * Retrieve format price.
     *
     * @return string
     */
    public function getDisplayPrice()
    {
        $defaultData = $this->getPriceData();
        $result = $this->formatCurrency($defaultData['price'], true);
        if ($defaultData['frequency_unit_message']) {
            $result .= $defaultData['frequency_unit_message'];
        }
        return $result;
    }

    /**
     * Retrieve price data from product and billing frequency.
     *
     * @return array|null
     */
    private function getPriceData()
    {
        return $this->getData('billing_frequency');
    }
}
