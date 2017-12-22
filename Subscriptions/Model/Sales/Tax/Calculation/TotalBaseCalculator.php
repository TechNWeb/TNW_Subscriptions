<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Sales\Tax\Calculation;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Calculation\TotalBaseCalculator as MagentoTotalCalculator;

/**
 * Subscription total base tax calculator.
 */
class TotalBaseCalculator extends MagentoTotalCalculator implements CalculatorInterface
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

        $discountTaxCompensationAmount = 0;
        $applyTaxAfterDiscount = $this->config->applyTaxAfterDiscount($this->storeId);
        $discountAmount = $item->getDiscountAmount();

        // Calculate $rowTotalInclTax
        $priceInclTax = $this->calculationTool->round($item->getUnitPrice());
        $rowTotalInclTax = $priceInclTax * $quantity;

        //Add logic for preset qty for subscription products
        if ($item->getData('subscription_use_preset_qty') && $item->getData('subscription_preset_qty_price')) {
            $rowTotalInclTax = $this->calculationTool->round($item->getData('subscription_preset_qty_price'));
        }

        if (!$this->isSameRateAsStore($rate, $storeRate)) {
            $priceInclTax = $this->calculatePriceInclTax($priceInclTax, $storeRate, $rate, $round);
            $rowTotalInclTax = $priceInclTax * $quantity;
            //Add logic for preset qty for subscription products
            if ($item->getData('subscription_use_preset_qty') && $item->getData('subscription_preset_qty_price')) {
                $rowTotalInclTax = $this->calculatePriceInclTax(
                    $item->getData('subscription_preset_qty_price'),
                    $storeRate,
                    $rate,
                    $round
                );
            }
        }
        $rowTaxExact = $this->calculationTool->calcTaxAmount($rowTotalInclTax, $rate, true, false);
        $deltaRoundingType = self::KEY_REGULAR_DELTA_ROUNDING;
        if ($applyTaxAfterDiscount) {
            $deltaRoundingType = self::KEY_TAX_BEFORE_DISCOUNT_DELTA_ROUNDING;
        }
        $rowTax = $this->roundAmount($rowTaxExact, $rate, true, $deltaRoundingType, $round, $item);

        $rowTotal = $rowTotalInclTax - $rowTax;
        $price = $quantity ? $rowTotal / $quantity : 0;

        //Add logic for preset qty for subscription products
        if ($item->getData('subscription_use_preset_qty') && $item->getData('subscription_preset_qty_price')) {
            $rowTotal = $this->calculationTool->round($item->getData('subscription_preset_qty_price'));
            $price = $item->getUnitPrice();
        }

        if ($round) {
            $price = $this->calculationTool->round($price);
        }

        //Handle discount
        if ($applyTaxAfterDiscount) {
            //TODO: handle originalDiscountAmount
            $taxableAmount = max($rowTotalInclTax - $discountAmount, 0);
            $rowTaxAfterDiscount = $this->calculationTool->calcTaxAmount(
                $taxableAmount,
                $rate,
                true,
                false
            );
            $rowTaxAfterDiscount = $this->roundAmount(
                $rowTaxAfterDiscount,
                $rate,
                true,
                self::KEY_REGULAR_DELTA_ROUNDING,
                $round,
                $item
            );
            // Set discount tax compensation
            $discountTaxCompensationAmount = $rowTax - $rowTaxAfterDiscount;
            $rowTax = $rowTaxAfterDiscount;
        }

        // Calculate applied taxes
        /** @var  \Magento\Tax\Api\Data\AppliedTaxInterface[] $appliedTaxes */
        $appliedRates = $this->calculationTool->getAppliedRates($taxRateRequest);
        $appliedTaxes = $this->getAppliedTaxes($rowTax, $rate, $appliedRates);

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

        $applyTaxAfterDiscount = $this->config->applyTaxAfterDiscount($this->storeId);
        $discountAmount = $item->getDiscountAmount();
        $discountTaxCompensationAmount = 0;

        // Calculate $rowTotal
        $price = $this->calculationTool->round($item->getUnitPrice());
        $rowTotal = $price * $quantity;

        //Add logic for preset qty for subscription products
        if ($item->getData('subscription_use_preset_qty') && $item->getData('subscription_preset_qty_price')) {
            $rowTotal = $this->calculationTool->round($item->getData('subscription_preset_qty_price'));
        }

        $rowTaxes = [];
        $rowTaxesBeforeDiscount = [];
        $appliedTaxes = [];
        //Apply each tax rate separately
        foreach ($appliedRates as $appliedRate) {
            $taxId = $appliedRate['id'];
            $taxRate = $appliedRate['percent'];
            $rowTaxPerRate = $this->calculationTool->calcTaxAmount($rowTotal, $taxRate, false, false);
            $deltaRoundingType = self::KEY_REGULAR_DELTA_ROUNDING;
            if ($applyTaxAfterDiscount) {
                $deltaRoundingType = self::KEY_TAX_BEFORE_DISCOUNT_DELTA_ROUNDING;
            }
            $rowTaxPerRate = $this->roundAmount($rowTaxPerRate, $taxId, false, $deltaRoundingType, $round, $item);
            $rowTaxAfterDiscount = $rowTaxPerRate;

            //Handle discount
            if ($applyTaxAfterDiscount) {
                //TODO: handle originalDiscountAmount
                $taxableAmount = max($rowTotal - $discountAmount, 0);
                $rowTaxAfterDiscount = $this->calculationTool->calcTaxAmount(
                    $taxableAmount,
                    $taxRate,
                    false,
                    false
                );
                $rowTaxAfterDiscount = $this->roundAmount(
                    $rowTaxAfterDiscount,
                    $taxId,
                    false,
                    self::KEY_REGULAR_DELTA_ROUNDING,
                    $round,
                    $item
                );
            }
            $appliedTaxes[$taxId] = $this->getAppliedTax(
                $rowTaxAfterDiscount,
                $appliedRate
            );

            $rowTaxes[] = $rowTaxAfterDiscount;
            $rowTaxesBeforeDiscount[] = $rowTaxPerRate;
        }
        $rowTax = array_sum($rowTaxes);
        $rowTaxBeforeDiscount = array_sum($rowTaxesBeforeDiscount);
        $rowTotalInclTax = $rowTotal + $rowTaxBeforeDiscount;
        $priceInclTax = $quantity ? $rowTotalInclTax / $quantity : 0;
        if ($round) {
            $priceInclTax = $this->calculationTool->round($priceInclTax);
        }

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
