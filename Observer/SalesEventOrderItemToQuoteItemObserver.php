<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;

/**
 * Class SalesEventOrderItemToQuoteItemObserver - observer
 */
class SalesEventOrderItemToQuoteItemObserver implements ObserverInterface
{
    /**
     * Subscription quote and order items extension manager.
     *
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * ToOrderItem constructor.
     * @param ExtensionManager $extensionManager
     */
    public function __construct(
        ExtensionManager $extensionManager
    ) {
        $this->extensionManager = $extensionManager;
    }

    /**
     * Duplicates subscription initial fees from order item to quote item on import or reorder
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var OrderItem $orderItem */
        $orderItem = $observer->getEvent()->getOrderItem();
        /** @var QuoteItem $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $orderExtAttributes = $orderItem->getExtensionAttributes()
            ? $orderItem->getExtensionAttributes()->getSubsInitialFees()
            : null;
        if ($orderExtAttributes) {
            $quoteInitialFees = $this->extensionManager->convertOrderItemToQuoteItem($orderExtAttributes);
            $quoteExtAttributes = $quoteItem->getExtensionAttributes()
                ?: $this->extensionManager->getEmptyCartItemExtension();
            $quoteExtAttributes->setSubsInitialFees($quoteInitialFees);
            $quoteItem->setExtensionAttributes($quoteExtAttributes);
        }

        return $this;
    }
}
