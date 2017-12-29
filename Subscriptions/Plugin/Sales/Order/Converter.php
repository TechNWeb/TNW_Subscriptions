<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Order;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Convert\Order;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\InvoiceItem;

/**
 * Plugin for order converter class.
 */
class Converter
{
    /**
     * Subscription invoice items extension attribute manager.
     *
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * @param ExtensionManager $extensionManager
     */
    public function __construct(
        ExtensionManager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Converts order item initial fee to invoice item initial fee.
     *
     * @param Order $subject
     * @param \Closure $proceed
     * @param InvoiceItem $item
     * @return InvoiceItem
     */
    public function aroundItemToInvoiceItem(
        Order $subject,
        \Closure $proceed,
        OrderItemInterface $item
    ) {
        /** @var InvoiceItem $orderItem */
        $invoiceItem = $proceed($item);
        /** @var InvoiceItem $item */
        $orderItemInitialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($orderItemInitialFees) {
            $invoiceItemInitialFees = $this->extensionManager->convertOrderItemToInvoiceItem($orderItemInitialFees);
            $invoiceExtAttributes = $invoiceItem->getExtensionAttributes()
                ?: $this->extensionManager->getEmptyInvoiceItemExtension();
            $invoiceExtAttributes->setSubsInitialFees($invoiceItemInitialFees);
            $invoiceItem->setExtensionAttributes($invoiceExtAttributes);
        }

        return $invoiceItem;
    }
}
