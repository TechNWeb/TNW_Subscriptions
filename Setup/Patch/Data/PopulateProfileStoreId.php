<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Setup\Patch\Data;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory as SubscriptionProfileCollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Patch to populate store_id from first recurring order into profile
 */
class PopulateProfileStoreId implements DataPatchInterface
{
    /**
     * @var SubscriptionProfileCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;

    /**
     * @var State
     */
    private $appState;

    /**
     * PopulateProfileStoreId constructor.
     * @param SubscriptionProfileCollectionFactory $collectionFactory
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param State $appState
     */
    public function __construct(
        SubscriptionProfileCollectionFactory $collectionFactory,
        CollectionFactoryInterface $orderCollectionFactory,
        State $appState
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->appState = $appState;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $e) {
        }
        $collection = $this->collectionFactory->create();
        $profiles = $collection->getItems();
        $orderIds = [];
        /** @var SubscriptionProfile $profile */
        foreach ($profiles as $profile) {
            $firstOrderId = $profile->getFirstOrderData()['magento_order_id'];
            $orderIds[$profile->getId()] = $firstOrderId;
        }

        $orders = $this->orderCollectionFactory->create()->addFieldToSelect(['entity_id', 'store_id'])
            ->addFieldToFilter('entity_id', ['in' => $orderIds])->getItems();
        $orderToStore = [];
        /** @var Order $order */
        foreach ($orders as $order) {
            $orderToStore[$order->getId()] = $order->getStoreId();
        }

        foreach ($profiles as $profile) {
            $storeId = $orderToStore[$orderIds[$profile->getId()]];
            if ($storeId) {
                $profile->setStoreId($storeId);
            }
        }
        $collection->save();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
