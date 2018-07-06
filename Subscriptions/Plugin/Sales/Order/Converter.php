<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Order;

use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Model\Convert\Order;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;

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
     * @param OrderItemInterface $item
     * @return InvoiceItemInterface
     */
    public function aroundItemToInvoiceItem(
        Order $subject,
        \Closure $proceed,
        OrderItemInterface $item
    ) {
        /** @var InvoiceItemInterface $invoiceItem */
        $invoiceItem = $proceed($item);
        $orderItemInitialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($orderItemInitialFees) {
            $invoiceItemInitialFees = $this->extensionManager->convertOrderItemToInvoiceItem(
                $orderItemInitialFees
            );
            $invoiceExtAttributes = $invoiceItem->getExtensionAttributes()
                ?: $this->extensionManager->getEmptyInvoiceItemExtension();
            $invoiceExtAttributes->setSubsInitialFees($invoiceItemInitialFees);
            $invoiceItem->setExtensionAttributes($invoiceExtAttributes);
        }

        return $invoiceItem;
    }

    /**
     * Converts order item initial fee to credit memo item initial fee.
     * Also look TNW\Subscriptions\Model\Sales\Total\Creditmemo\InitialFee,
     * initial fees for credit memo can be redefined there
     *
     * @param Order $subject
     * @param \Closure $proceed
     * @param OrderItemInterface $item
     * @return CreditmemoItemInterface
     */
    public function aroundItemToCreditmemoItem(
        Order $subject,
        \Closure $proceed,
        OrderItemInterface $item
    ) {
        /** @var CreditmemoItemInterface $orderItem */
        $creditMemoItem = $proceed($item);
        $orderItemInitialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($orderItemInitialFees) {
            $creditMemoInitialFees = $this->extensionManager->getEmptyCreditmemoItemAttribute();
            $creditMemoExtAttributes = $creditMemoItem->getExtensionAttributes()
                ?: $this->extensionManager->getEmptyCreditmemoItemExtension();
            $creditMemoExtAttributes->setSubsInitialFees($creditMemoInitialFees);
            $creditMemoItem->setExtensionAttributes($creditMemoExtAttributes);
        }

        return $creditMemoItem;
    }
}
