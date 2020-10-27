<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Calculation\AbstractCalculator as MagentoCalculator;

/**
 * Plugin for tax calculator model.
 */
class AbstractCalculator
{
    /**
     * @param MagentoCalculator $subject
     * @param \Closure $proceed
     * @param QuoteDetailsItemInterface $item
     * @param int $quantity
     * @param bool $round
     * @return \Magento\Tax\Api\Data\TaxDetailsItemInterface
     */
    public function aroundCalculate(
        MagentoCalculator $subject,
        \Closure $proceed,
        QuoteDetailsItemInterface $item,
        $quantity,
        $round = true
    ) {
        if ($item->getData('subscription_use_preset_qty')
            && $item->getData('subscription_preset_qty_price')
        ) {
            $result = $proceed($item, 1, $round);
            if (false === $result) {
                $result = $proceed($item, $quantity, $round);
            }
        } else {
            $result = $proceed($item, $quantity, $round);
        }
        return $result;
    }
}
