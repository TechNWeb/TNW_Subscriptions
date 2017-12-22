<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\Tax\Calculation;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Api\Data\TaxDetailsItemInterface;

/**
 * Interface for subscription calculators.
 */
interface CalculatorInterface
{
    /**
     * Calculate tax details for quote item with given quantity for subscription.
     *
     * @param QuoteDetailsItemInterface $item
     * @param int $quantity
     * @param bool $round
     * @return TaxDetailsItemInterface
     */
    public function subscriptionCalculate(
        QuoteDetailsItemInterface $item,
        $quantity,
        $round = true
    );
}
