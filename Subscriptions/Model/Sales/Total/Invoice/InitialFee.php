<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Invoice initial fee totals collector.
 */
class InitialFee extends AbstractTotal
{
    /**
     * Subscription initial fee totals collector.
     * Adds initial fee to grand total amount (without taxes).
     * Initial fee is order item extension attribute.
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $totalInitialFee = 0;
        $baseTotalInitialFee = 0;
        /** @var InvoiceItem $item */
        foreach ($invoice->getAllItems() as $item) {
            /** @var OrderItem $orderItem */
            $orderItem = $item->getOrderItem();
            $orderItemQty = $orderItem->getQtyOrdered();

            if ($orderItem->isDummy() || $item->getQty() < 0) {
                continue;
            }

            list($currentFee, $baseCurrentFee) = $this->getItemCurrentInitialFees($orderItem);

            if ($currentFee && $orderItemQty) {
                if (!$item->isLast()) {
                    $ratio = ($orderItemQty - $orderItem->getQtyInvoiced() > 0)
                        ? $item->getQty() / ($orderItemQty - $orderItem->getQtyInvoiced())
                        : 0;
                    $currentFee = $invoice->roundPrice($currentFee * $ratio);
                    $baseCurrentFee = $invoice->roundPrice($baseCurrentFee * $ratio, 'base');
                }

                $this->setItemInitialFees($item, $currentFee, $baseCurrentFee);
                $totalInitialFee += $currentFee;
                $baseTotalInitialFee += $baseCurrentFee;
            }
        }

        $invoice->setGrandTotal($invoice->getGrandTotal() + $totalInitialFee);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseTotalInitialFee);

        return $this;
    }

    /**
     * Returns order item subscription initial fees extension attribute.
     *
     * @param OrderItem $item
     * @return array
     */
    private function getItemCurrentInitialFees(OrderItem $item)
    {
        $initialFee = 0;
        $baseInitialFee = 0;
        $initialFeeInvoiced = 0;
        $baseInitialFeeInvoiced = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;

        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
            $initialFeeInvoiced = $initialFees->getSubsInitialFeeInvoiced();
            $baseInitialFeeInvoiced = $initialFees->getBaseSubsInitialFeeInvoiced();
        }

        return [$initialFee - $initialFeeInvoiced, $baseInitialFee - $baseInitialFeeInvoiced];
    }

    /**
     * Sets to invoice item subscription initial fee.
     *
     * @param InvoiceItem $item
     * @param float $fee
     * @param float $baseFee
     * @return void
     */
    private function setItemInitialFees(InvoiceItem $item, $fee, $baseFee)
    {
        if ($item->getExtensionAttributes()
            && $item->getExtensionAttributes()->getSubsInitialFees()
        ) {
            $item->getExtensionAttributes()->getSubsInitialFees()
                ->setSubsInitialFee($fee)
                ->setBaseSubsInitialFee($baseFee);

        }
    }
}
