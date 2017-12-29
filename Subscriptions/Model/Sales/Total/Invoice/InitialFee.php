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
            if ($orderItem->isDummy()) {
                continue;
            }
            list($initialFee, $baseInitialFee) = $this->getItemInitialFees($orderItem);
            $totalInitialFee += $initialFee;
            $baseTotalInitialFee += $baseInitialFee;
        }
        // in our case we don't need to check is this invoice is last because subscription can be
        // created only with credit payment method and in this case we will have only one invoice
        $invoice->setSubtotal($invoice->getSubtotal() + $totalInitialFee);
        $invoice->setBaseSubtotal($invoice->getBaseSubtotal()  + $baseTotalInitialFee);
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
    private function getItemInitialFees(OrderItem $item)
    {
        $initialFee = 0;
        $baseInitialFee = 0;
        $initialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($initialFees) {
            $initialFee = $initialFees->getSubsInitialFee();
            $baseInitialFee = $initialFees->getBaseSubsInitialFee();
        }

        return [$initialFee, $baseInitialFee];
    }
}
