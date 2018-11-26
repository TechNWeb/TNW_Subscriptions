<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Invoice;

use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\InvoiceItem as InvoiceInitialFees;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem as OrderInitialFees;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Invoice item class plugin for updating subscription initial fee after register/cancel invoice.
 */
class Item
{
    /**
     * Updates invoiced subscription initial fee to order item after register invoice.
     *
     * @param InvoiceItem $subject
     * @param InvoiceItem $result
     * @return InvoiceItem
     */
    public function afterRegister(
        InvoiceItem $subject,
        InvoiceItem $result
    ) {
        $orderItem = $result->getOrderItem();
        $orderInitialFees = $this->getOrderItemInitialFees($orderItem);
        $invoiceInitialFees = $this->getInvoiceItemInitialFees($result);

        if ($orderInitialFees && $invoiceInitialFees) {
            $orderInitialFees->setSubsInitialFeeInvoiced(
                $orderInitialFees->getSubsInitialFeeInvoiced() + $invoiceInitialFees->getSubsInitialFee()
            );
            $orderInitialFees->setBaseSubsInitialFeeInvoiced(
                $orderInitialFees->getBaseSubsInitialFeeInvoiced() + $invoiceInitialFees->getBaseSubsInitialFee()
            );

            if ($orderItem->getExtensionAttributes()) {
                $orderItem->getExtensionAttributes()->setSubsInitialFees($orderInitialFees);
            }
        }

        return $result;
    }

    /**
     * Updates invoiced subscription initial fee to order item after cancel invoice.
     *
     * @param InvoiceItem $subject
     * @param InvoiceItem $result
     * @return InvoiceItem
     */
    public function afterCancel(
        InvoiceItem $subject,
        InvoiceItem $result
    ) {
        $orderItem = $result->getOrderItem();
        $orderInitialFees = $this->getOrderItemInitialFees($orderItem);
        $invoiceInitialFees = $this->getInvoiceItemInitialFees($result);

        if ($orderInitialFees && $invoiceInitialFees) {
            $orderInitialFees->setSubsInitialFeeInvoiced(
                $orderInitialFees->getSubsInitialFeeInvoiced() - $invoiceInitialFees->getSubsInitialFee()
            );
            $orderInitialFees->setBaseSubsInitialFeeInvoiced(
                $orderInitialFees->getBaseSubsInitialFeeInvoiced() - $invoiceInitialFees->getBaseSubsInitialFee()
            );

            if ($orderItem->getExtensionAttributes()) {
                $orderItem->getExtensionAttributes()->setSubsInitialFees($orderInitialFees);
            }
        }

        return $result;
    }

    /**
     * Returns order item initial fee extension attribute.
     *
     * @param OrderItem $orderItem
     * @return null|OrderInitialFees
     */
    private function getOrderItemInitialFees(OrderItem $orderItem)
    {
        return $orderItem->getExtensionAttributes()
            ? $orderItem->getExtensionAttributes()->getSubsInitialFees()
            : null;
    }

    /**
     * Returns invoice item initial fee extension attribute.
     *
     * @param InvoiceItem $invoiceItem
     * @return null|InvoiceInitialFees
     */
    private function getInvoiceItemInitialFees(InvoiceItem $invoiceItem)
    {
        return $invoiceItem->getExtensionAttributes()
            ? $invoiceItem->getExtensionAttributes()->getSubsInitialFees()
            : null;
    }
}
