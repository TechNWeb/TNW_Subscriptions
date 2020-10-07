<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Pricing\Price;

use Magento\Framework\Pricing\Price\AbstractPrice;

/**
 * Final price model
 */
class SubscriptionPrice extends AbstractPrice
{
    /**
     * Price type final
     */
    const PRICE_CODE = 'subscription_price';

    /**
     * @var \Magento\Framework\Pricing\Price\PriceInterface
     */
    private $basePrice;

    /**
     * @var \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected $minimalPrice;

    /**
     * @var \Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected $maximalPrice;

    /**
     * Get Value
     *
     * @return float|bool
     */
    public function getValue()
    {
        return max(0, $this->getBasePrice()->getValue());
    }

    /**
     * Retrieve base price instance lazily
     *
     * @return \Magento\Framework\Pricing\Price\PriceInterface
     */
    protected function getBasePrice()
    {
        if (!$this->basePrice) {
            $this->basePrice = $this->priceInfo->getPrice(self::PRICE_CODE);
        }
        return $this->basePrice;
    }
}
