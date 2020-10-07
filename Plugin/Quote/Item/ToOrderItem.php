<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Quote\Item;

use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem as MagentoToOrderItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;

/**
 * Class ToOrderItem - plugin to add additional logic to MagentoToOrderItem convert method
 */
class ToOrderItem
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
     * @param MagentoToOrderItem $subject
     * @param \Closure $proceed
     * @param AbstractItem $item
     * @param array $additional
     * @return OrderItem
     */
    public function aroundConvert(
        MagentoToOrderItem $subject,
        \Closure $proceed,
        AbstractItem $item,
        $additional = []
    ) {
        /** @var OrderItem $orderItem */
        $orderItem = $proceed($item, $additional);
        /** @var QuoteItem $item */
        $quoteInitialFees = $item->getExtensionAttributes()
            ? $item->getExtensionAttributes()->getSubsInitialFees()
            : null;
        /** initial fee should be applied for the first order only */
        if ($quoteInitialFees && !$item->getQuote()->getScheduled()) {
            $orderInitialFees = $this->extensionManager->convertQuoteItemToOrderItem($quoteInitialFees);
            $orderExtAttributes = $orderItem->getExtensionAttributes()
                ?: $this->extensionManager->getEmptyOrderItemExtension();
            $orderExtAttributes->setSubsInitialFees($orderInitialFees);
            $orderItem->setExtensionAttributes($orderExtAttributes);
        }

        return $orderItem;
    }
}
