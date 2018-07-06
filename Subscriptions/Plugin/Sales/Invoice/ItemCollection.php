<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Invoice;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\InvoiceItem\Collection as ExtensionCollection;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\InvoiceItem\CollectionFactory;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\InvoiceItem;

/**
 * Invoice item collection plugin.
 */
class ItemCollection
{
    /**
     * Subscription invoice item extension attributes manager.
     *
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * Subscription invoice item extension attributes collection.
     *
     * @var CollectionFactory
     */
    private $extensionCollection;

    /**
     * @param ExtensionManager $extensionManager
     * @param CollectionFactory $extensionCollection
     */
    public function __construct(
        ExtensionManager $extensionManager,
        CollectionFactory $extensionCollection
    ) {
        $this->extensionManager = $extensionManager;
        $this->extensionCollection = $extensionCollection;
    }

    /**
     * Add subscription initial fee extension attributes to invoice items.
     *
     * @param InvoiceInterface $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterGetItemsCollection(InvoiceInterface $subject, Collection $result)
    {
        $invoiceItemIds = array_keys($result->getItems());
        if ($invoiceItemIds) {
            /** @var InvoiceItem[] $initialFees */
            $initialFees = $this->getInvoiceItemsInitialFees($invoiceItemIds)->getItems();
            /** @var InvoiceItemInterface $item */
            foreach ($result as $item) {
                if (isset($initialFees[$item->getId()])) {
                    $invoiceExtAttributes = $item->getExtensionAttributes()
                        ?: $this->extensionManager->getEmptyInvoiceItemExtension();
                    $invoiceExtAttributes->setSubsInitialFees($initialFees[$item->getId()]);
                    $item->setExtensionAttributes($invoiceExtAttributes);
                }
            }
        }

        return $result;
    }

    /**
     * Returns collection of invoice item extension attributes.
     *
     * @param array $invoiceItemIds
     * @return ExtensionCollection
     */
    private function getInvoiceItemsInitialFees(array $invoiceItemIds)
    {
        /** @var ExtensionCollection $collection */
        $collection = $this->extensionCollection->create();
        $collection->addFieldToFilter(
            SalesExtensionAttributesInterface::MAGENTO_ITEM_ID,
            ['in' => $invoiceItemIds]
        );

        return $collection;
    }
}
