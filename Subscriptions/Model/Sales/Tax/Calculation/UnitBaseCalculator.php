<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Sales\Tax\Calculation;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Calculation\UnitBaseCalculator as MagentoUnitCalculator;

/**
 * Subscription unit base tax calculator.
 */
class UnitBaseCalculator extends MagentoUnitCalculator implements CalculatorInterface
{
    /**
     * @inheritdoc
     */
    protected function calculateWithTaxInPrice(QuoteDetailsItemInterface $item, $quantity, $round = true)
    {
        $taxRateRequest = $this->getAddressRateRequest()->setProductClassId(
            $this->taxClassManagement->getTaxClassId($item->getTaxClassKey())
        );
        $rate = $this->calculationTool->getRate($taxRateRequest);
        $storeRate = $storeRate = $this->calculationTool->getStoreRate($taxRateRequest, $this->storeId);
        // Calculate $priceInclTax
        $applyTaxAfterDiscount = $this->config->applyTaxAfterDiscount($this->storeId);

        $priceInclTax = $this->calculationTool->round($item->getUnitPrice());
        if (!$this->isSameRateAsStore($rate, $storeRate)) {
            $priceInclTax = $this->calculatePriceInclTax($priceInclTax, $storeRate, $rate, $round);
        }

        $uniTax = $this->calculationTool->calcTaxAmount($priceInclTax, $rate, true, false);
        $deltaRoundingType = self::KEY_REGULAR_DELTA_ROUNDING;
        if ($applyTaxAfterDiscount) {
            $deltaRoundingType = self::KEY_TAX_BEFORE_DISCOUNT_DELTA_ROUNDING;
        }
        $uniTax = $this->roundAmount($uniTax, $rate, true, $deltaRoundingType, $round, $item);
        $price = $priceInclTax - $uniTax;

        //Handle discount
        $discountTaxCompensationAmount = 0;
        $discountAmount = $item->getDiscountAmount();
        if ($applyTaxAfterDiscount) {
            //TODO: handle originalDiscountAmount
            $unitDiscountAmount = $quantity ? $discountAmount / $quantity : 0;
            $taxableAmount = max($priceInclTax - $unitDiscountAmount, 0);
            $unitTaxAfterDiscount = $this->calculationTool->calcTaxAmount(
                $taxableAmount,
                $rate,
                true,
                false
            );
            $unitTaxAfterDiscount = $this->roundAmount(
                $unitTaxAfterDiscount,
                $rate,
                true,
                self::KEY_REGULAR_DELTA_ROUNDING,
                $round,
                $item
            );

            // Set discount tax compensation
            $unitDiscountTaxCompensationAmount = $uniTax - $unitTaxAfterDiscount;
            $discountTaxCompensationAmount = $unitDiscountTaxCompensationAmount * $quantity;
            $uniTax = $unitTaxAfterDiscount;
        }
        $rowTax = $uniTax * $quantity;

        //Add logic for preset qty for subscription products
        if ($item->getData('subscription_use_preset_qty') && $item->getData('subscription_preset_qty_price')) {
            $rowTotalInclTax = $this->calculationTool->round($item->getData('subscription_preset_qty_price'));
            if (!$this->isSameRateAsStore($rate, $storeRate)) {
                $rowTotalInclTax = $this->calculatePriceInclTax(
                    $item->getData('subscription_preset_qty_price'),
                    $storeRate,
                    $rate,
                    $round
                );
            }
            $rowUniTax = $this->calculationTool->calcTaxAmount($rowTotalInclTax, $rate, true, false);
            $deltaRoundingType = self::KEY_REGULAR_DELTA_ROUNDING;
            if ($applyTaxAfterDiscount) {
                $deltaRoundingType = self::KEY_TAX_BEFORE_DISCOUNT_DELTA_ROUNDING;
            }
            $rowUniTax = $this->roundAmount($rowUniTax, $rate, true, $deltaRoundingType, $round, $item);
            $rowTotal = $rowTotalInclTax - $rowUniTax;
            $rowTax = $rowUniTax;
        }

        // Calculate applied taxes
        /** @var  \Magento\Tax\Api\Data\AppliedTaxInterface[] $appliedTaxes */
        $appliedRates = $this->calculationTool->getAppliedRates($taxRateRequest);
        $appliedTaxes = $this->getAppliedTaxes($rowTax, $rate, $appliedRates);

        $rowTotal = isset($rowTotal) ? $rowTotal : $price * $quantity;
        $rowTotalInclTax = isset($rowTotalInclTax) ? $rowTotalInclTax : $priceInclTax * $quantity;

        return $this->taxDetailsItemDataObjectFactory->create()
            ->setCode($item->getCode())
            ->setType($item->getType())
            ->setRowTax($rowTax)
            ->setPrice($price)
            ->setPriceInclTax($priceInclTax)
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotalInclTax)
            ->setDiscountTaxCompensationAmount($discountTaxCompensationAmount)
            ->setAssociatedItemCode($item->getAssociatedItemCode())
            ->setTaxPercent($rate)
            ->setAppliedTaxes($appliedTaxes);
    }

    /**
     * @inheritdoc
     */
    protected function calculateWithTaxNotInPrice(QuoteDetailsItemInterface $item, $quantity, $round = true)
    {
        $taxRateRequest = $this->getAddressRateRequest()->setProductClassId(
            $this->taxClassManagement->getTaxClassId($item->getTaxClassKey())
        );
        $rate = $this->calculationTool->getRate($taxRateRequest);
        $appliedRates = $this->calculationTool->getAppliedRates($taxRateRequest);
        $discountTaxCompensationAmount = 0;
        // Calculate $price
        $price = $this->calculationTool->round($item->getUnitPrice());
        // default logic
        list($unitTaxes, $unitTaxesBeforeDiscount, $appliedTaxes) = $this->applyTaxes(
            $item,
            $quantity,
            $round,
            $appliedRates,
            $price
        );
        $unitTax = array_sum($unitTaxes);
        $unitTaxBeforeDiscount = array_sum($unitTaxesBeforeDiscount);
        $rowTax = $unitTax * $quantity;
        $priceInclTax = $price + $unitTaxBeforeDiscount;
        //Add logic for preset qty for subscription products
        if ($item->getData('subscription_use_preset_qty') && $item->getData('subscription_preset_qty_price')) {
            $rowTotal = $this->calculationTool->round($item->getData('subscription_preset_qty_price'));
            list($rowUnitTaxes, $rowUnitTaxesBeforeDiscount) = $this->applyTaxes(
                $item,
                1,
                $round,
                $appliedRates,
                $rowTotal
            );
            $rowTax = array_sum($rowUnitTaxes);
            $rowTotalInclTax = $rowTotal + array_sum($rowUnitTaxesBeforeDiscount);
        }

        $rowTotal = isset($rowTotal) ? $rowTotal : $price * $quantity;
        $rowTotalInclTax = isset($rowTotalInclTax) ? $rowTotalInclTax : $priceInclTax * $quantity;

        return $this->taxDetailsItemDataObjectFactory->create()
            ->setCode($item->getCode())
            ->setType($item->getType())
            ->setRowTax($rowTax)
            ->setPrice($price)
            ->setPriceInclTax($priceInclTax)
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotalInclTax)
            ->setDiscountTaxCompensationAmount($discountTaxCompensationAmount)
            ->setAssociatedItemCode($item->getAssociatedItemCode())
            ->setTaxPercent($rate)
            ->setAppliedTaxes($appliedTaxes);
    }

    /**
     * Apply rates to specific price.
     *
     * @param QuoteDetailsItemInterface $item
     * @param int $quantity
     * @param bool $round
     * @param array $appliedRates
     * @param string/float $price
     * @return array
     */
    protected function applyTaxes(
        QuoteDetailsItemInterface $item,
        $quantity,
        $round,
        $appliedRates,
        $price
    ) {
        $applyTaxAfterDiscount = $this->config->applyTaxAfterDiscount($this->storeId);
        $unitTaxes = [];
        $unitTaxesBeforeDiscount = [];
        $appliedTaxes = [];
        $discountAmount = $item->getDiscountAmount();
        //Apply each tax rate separately
        foreach ($appliedRates as $appliedRate) {
            $taxId = $appliedRate['id'];
            $taxRate = $appliedRate['percent'];
            $unitTaxPerRate = $this->calculationTool->calcTaxAmount($price, $taxRate, false, false);
            $deltaRoundingType = self::KEY_REGULAR_DELTA_ROUNDING;
            if ($applyTaxAfterDiscount) {
                $deltaRoundingType = self::KEY_TAX_BEFORE_DISCOUNT_DELTA_ROUNDING;
            }
            $unitTaxPerRate = $this->roundAmount($unitTaxPerRate, $taxId, false, $deltaRoundingType, $round, $item);
            $unitTaxAfterDiscount = $unitTaxPerRate;

            //Handle discount
            if ($applyTaxAfterDiscount) {
                //TODO: handle originalDiscountAmount
                $unitDiscountAmount = $quantity ? $discountAmount / $quantity : 0;
                $taxableAmount = max($price - $unitDiscountAmount, 0);
                $unitTaxAfterDiscount = $this->calculationTool->calcTaxAmount(
                    $taxableAmount,
                    $taxRate,
                    false,
                    false
                );
                $unitTaxAfterDiscount = $this->roundAmount(
                    $unitTaxAfterDiscount,
                    $taxId,
                    false,
                    self::KEY_REGULAR_DELTA_ROUNDING,
                    $round,
                    $item
                );
            }
            $appliedTaxes[$taxId] = $this->getAppliedTax(
                $unitTaxAfterDiscount * $quantity,
                $appliedRate
            );

            $unitTaxes[] = $unitTaxAfterDiscount;
            $unitTaxesBeforeDiscount[] = $unitTaxPerRate;
        }

        return array($unitTaxes, $unitTaxesBeforeDiscount, $appliedTaxes);
    }

    /**
     * @inheritdoc
     */
    public function subscriptionCalculate(
        QuoteDetailsItemInterface $item,
        $quantity,
        $round = true
    ) {
        if ($item->getIsTaxIncluded()) {
            $result = $this->calculateWithTaxInPrice($item, $quantity, $round);
        } else {
            $result = $this->calculateWithTaxNotInPrice($item, $quantity, $round);
        }

        return $result;
    }
}
