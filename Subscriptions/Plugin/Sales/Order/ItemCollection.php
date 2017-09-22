<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Order;

use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\ExtensionManager;
use TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\OrderItem\Collection as ExtensionCollection;
use TNW\Subscriptions\Model\ResourceModel\Sales\ExtensionAttributes\OrderItem\CollectionFactory;
use TNW\Subscriptions\Api\Data\SalesExtensionAttributesInterface;

/**
 * Class ItemCollection
 */
class ItemCollection
{
    /**
     * Subscription order item extension attributes manager.
     *
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * Subscription order item extension attributes collection.
     *
     * @var CollectionFactory
     */
    private $extensionCollection;

    /**
     * ItemCollection constructor.
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


    public function afterGetItemsCollection(OrderInterface $subject, Collection $result)
    {
        $orderItemIds = array_keys($result->getItems());
        if ($orderItemIds){
            /** @var OrderItem[] $initialFees */
            $initialFees = $this->getOrderItemsInitialFees($orderItemIds)->getItems();
            /** @var Item $item */
            foreach ($result as $item) {
                if (isset($initialFees[$item->getId()])){
                    $orderExtAttributes = $item->getExtensionAttributes()
                        ?: $this->extensionManager->getEmptyOrderItemExtension();
                    $orderExtAttributes->setSubsInitialFees($initialFees[$item->getId()]);
                    $item->setExtensionAttributes($orderExtAttributes);
                }
            }
        }

        return $result;
    }

    /**
     * Returns collection of order item extension attributes.
     *
     * @param array $orderItemIds
     * @return ExtensionCollection
     */
    private function getOrderItemsInitialFees($orderItemIds)
    {
        /** @var ExtensionCollection $collection */
        $collection = $this->extensionCollection->create();
        $collection->addFieldToFilter(
            SalesExtensionAttributesInterface::MAGENTO_ITEM_ID,
            ['in' => $orderItemIds]
        );

        return $collection;
    }
}