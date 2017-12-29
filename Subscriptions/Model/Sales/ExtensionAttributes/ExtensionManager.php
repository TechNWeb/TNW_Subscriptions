<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Sales\ExtensionAttributes;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Quote\Api\Data\CartItemExtension;
use Magento\Quote\Api\Data\CartItemExtensionFactory;
use Magento\Sales\Api\Data\InvoiceItemExtension;
use Magento\Sales\Api\Data\InvoiceItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemExtension;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Class extension attributes manager
 */
class ExtensionManager
{
    /**
     * Factory for creating order item extension attributes.
     *
     * @var OrderItemFactory
     */
    private $orderItemFactory;

    /**
     * Factory for creating subscription quote item extension attributes.
     *
     * @var QuoteItemFactory
     */
    private $quoteItemFactory;

    /**
     * Factory for creating subscription invoice item extension attributes.
     *
     * @var InvoiceItemFactory
     */
    private $invoiceItemFactory;

    /**
     * Factory for creating cart item extension attributes object.
     *
     * @var CartItemExtensionFactory
     */
    private $cartItemExtensionFactory;

    /**
     * Factory for creating cart item extension attributes object.
     *
     * @var OrderItemExtensionFactory
     */
    private $orderItemExtensionFactory;

    /**
     * Factory for creating invoice item extension attributes object.
     *
     * @var OrderItemExtensionFactory
     */
    private $invoiceItemExtensionFactory;

    /**
     * Data object helper.
     *
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @param OrderItemFactory $orderItemFactory
     * @param QuoteItemFactory $quoteItemFactory
     * @param InvoiceItemFactory $invoiceItemFactory
     * @param CartItemExtensionFactory $cartItemExtensionFactory
     * @param OrderItemExtensionFactory $orderItemExtensionFactory
     * @param InvoiceItemExtensionFactory $invoiceItemExtensionFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        OrderItemFactory $orderItemFactory,
        QuoteItemFactory $quoteItemFactory,
        InvoiceItemFactory $invoiceItemFactory,
        CartItemExtensionFactory $cartItemExtensionFactory,
        OrderItemExtensionFactory $orderItemExtensionFactory,
        InvoiceItemExtensionFactory $invoiceItemExtensionFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->orderItemFactory = $orderItemFactory;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->invoiceItemFactory = $invoiceItemFactory;
        $this->cartItemExtensionFactory = $cartItemExtensionFactory;
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
        $this->invoiceItemExtensionFactory = $invoiceItemExtensionFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Returns empty subscription order item extension attribute object.
     *
     * @return QuoteItem
     */
    public function getEmptyQuoteItemAttribute()
    {
        return $this->quoteItemFactory->create();
    }

    /**
     * Returns empty subscription order item extension attribute object.
     *
     * @return OrderItem
     */
    public function getEmptyOrderItemAttribute()
    {
        return $this->orderItemFactory->create();
    }

    /**
     * Returns empty subscription invoice item extension attribute object.
     *
     * @return InvoiceItem
     */
    public function getEmptyInvoiceItemAttribute()
    {
        return $this->invoiceItemFactory->create();
    }

    /**
     * Returns empty cart item extension attributes object.
     *
     * @return CartItemExtension
     */
    public function getEmptyCartItemExtension()
    {
        return $this->cartItemExtensionFactory->create();
    }

    /**
     * Returns empty order item extension attributes object.
     *
     * @return OrderItemExtension
     */
    public function getEmptyOrderItemExtension()
    {
        return $this->orderItemExtensionFactory->create();
    }

    /**
     * Returns empty invoice item extension attributes object.
     *
     * @return InvoiceItemExtension
     */
    public function getEmptyInvoiceItemExtension()
    {
        return $this->invoiceItemExtensionFactory->create();
    }

    /**
     * Converts quote item extension attribute to order item extension attribute.
     *
     * @param QuoteItem $item
     * @return OrderItem
     */
    public function convertQuoteItemToOrderItem(QuoteItem $item)
    {
        $orderItem = $this->getEmptyOrderItemAttribute();
        $this->dataObjectHelper->populateWithArray(
            $orderItem,
            $item->getData(),
            SalesExtensionAttributesInterface::class
        );

        return $orderItem;
    }

    /**
     * Converts order item extension attribute to quote item extension attribute.
     *
     * @param OrderItem $item
     * @return QuoteItem
     */
    public function convertOrderItemToQuoteItem(OrderItem $item)
    {
        $quoteItem = $this->getEmptyQuoteItemAttribute();
        $this->dataObjectHelper->populateWithArray(
            $quoteItem,
            $item->getData(),
            SalesExtensionAttributesInterface::class
        );

        return $quoteItem;
    }

    /**
     * Converts order item extension attribute to invoice item extension attribute.
     *
     * @param OrderItem $item
     * @return InvoiceItem
     */
    public function convertOrderItemToInvoiceItem(OrderItem $item)
    {
        $invoiceItem = $this->getEmptyInvoiceItemAttribute();
        $this->dataObjectHelper->populateWithArray(
            $invoiceItem,
            $item->getData(),
            SalesExtensionAttributesInterface::class
        );

        return $invoiceItem;
    }
}
