<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Quote;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Calculation\AbstractCalculator as MagentoCalculator;
use Magento\Tax\Model\Calculation\RowBaseCalculator as MagentoRowBaseCalculator;
use Magento\Tax\Model\Calculation\TotalBaseCalculator as MagentoTotalBaseCalculator;
use Magento\Tax\Model\Calculation\UnitBaseCalculator as MagentoUnitBaseCalculator;
use TNW\Subscriptions\Model\Sales\Tax\Calculation\RowBaseCalculator;
use TNW\Subscriptions\Model\Sales\Tax\Calculation\RowBaseCalculatorFactory;
use TNW\Subscriptions\Model\Sales\Tax\Calculation\TotalBaseCalculator;
use TNW\Subscriptions\Model\Sales\Tax\Calculation\TotalBaseCalculatorFactory;
use TNW\Subscriptions\Model\Sales\Tax\Calculation\UnitBaseCalculator;
use TNW\Subscriptions\Model\Sales\Tax\Calculation\UnitBaseCalculatorFactory;

/**
 * Plugin for tax calculator model.
 */
class AbstractCalculator
{
    /**
     * @var UnitBaseCalculatorFactory
     */
    private $unitBaseCalculatorFactory;

    /**
     * @var TotalBaseCalculatorFactory
     */
    private $totalBaseCalculatorFactory;

    /**
     * @var RowBaseCalculatorFactory
     */
    private $rowBaseCalculatorFactory;

    /**
     * @param UnitBaseCalculatorFactory $unitBaseCalculatorFactory
     * @param TotalBaseCalculatorFactory $totalBaseCalculatorFactory
     * @param RowBaseCalculatorFactory $rowBaseCalculatorFactory
     */
    public function __construct(
        UnitBaseCalculatorFactory $unitBaseCalculatorFactory,
        TotalBaseCalculatorFactory $totalBaseCalculatorFactory,
        RowBaseCalculatorFactory $rowBaseCalculatorFactory
    ) {
        $this->unitBaseCalculatorFactory = $unitBaseCalculatorFactory;
        $this->totalBaseCalculatorFactory = $totalBaseCalculatorFactory;
        $this->rowBaseCalculatorFactory = $rowBaseCalculatorFactory;
    }

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
            $result = $this->calculateForSubscription($subject, $item, $quantity, $round);
            if (false === $result) {
                $result = $proceed($item, $quantity, $round);
            }
        } else {
            $result = $proceed($item, $quantity, $round);
        }

        return $result;
    }

    /**
     * Calculates taxes for subcription products.
     *
     * @param MagentoCalculator $subject
     * @param QuoteDetailsItemInterface $item
     * @param int $quantity
     * @param bool $round
     * @return bool|\Magento\Tax\Api\Data\TaxDetailsItemInterface
     */
    private function calculateForSubscription(
        MagentoCalculator $subject,
        QuoteDetailsItemInterface $item,
        $quantity,
        $round
    ) {
        $result = false;
        $calculator = false;
        $params = ['storeId' => $item->getData('store_id')];
        if ($subject instanceof MagentoUnitBaseCalculator) {
            /** @var UnitBaseCalculator $calculator */
            $calculator = $this->unitBaseCalculatorFactory->create($params);
        } elseif ($subject instanceof MagentoTotalBaseCalculator) {
            /** @var TotalBaseCalculator $calculator */
            $calculator = $this->totalBaseCalculatorFactory->create($params);
        } elseif ($subject instanceof MagentoRowBaseCalculator) {
            /** @var RowBaseCalculator $calculator */
            $calculator = $this->rowBaseCalculatorFactory->create($params);
        }

        if ($calculator) {
            $result = $calculator->subscriptionCalculate($item, $quantity, $round);
        }

        return $result;
    }
}
