<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Creditmemo;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\CreditmemoItem\Collection as ExtensionCollection;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\CreditmemoItem\CollectionFactory;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\CreditmemoItem;

/**
 * Credit memo item collection plugin.
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
     * @param CreditmemoInterface $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterGetItemsCollection(CreditmemoInterface $subject, Collection $result)
    {
        $creditmemoItemIds = array_keys($result->getItems());
        if ($creditmemoItemIds) {
            /** @var CreditmemoItem[] $initialFees */
            $initialFees = $this->getCreditmemoItemsInitialFees($creditmemoItemIds)->getItems();
            /** @var CreditmemoItemInterface $item */
            foreach ($result as $item) {
                if (isset($initialFees[$item->getId()])) {
                    $invoiceExtAttributes = $item->getExtensionAttributes()
                        ?: $this->extensionManager->getEmptyCreditmemoItemExtension();
                    $invoiceExtAttributes->setSubsInitialFees($initialFees[$item->getId()]);
                    $item->setExtensionAttributes($invoiceExtAttributes);
                }
            }
        }

        return $result;
    }

    /**
     * Returns collection of credit memo item extension attributes.
     *
     * @param array $creditmemoItemIds
     * @return ExtensionCollection
     */
    private function getCreditmemoItemsInitialFees(array $creditmemoItemIds)
    {
        /** @var ExtensionCollection $collection */
        $collection = $this->extensionCollection->create();
        $collection->addFieldToFilter(
            SalesExtensionAttributesInterface::MAGENTO_ITEM_ID,
            ['in' => $creditmemoItemIds]
        );

        return $collection;
    }
}
