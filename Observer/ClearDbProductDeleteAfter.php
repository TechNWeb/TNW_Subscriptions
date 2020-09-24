<?php
/**
 * Copyright © 2020 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as MagentoProductCollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use TNW\Subscriptions\Model\SubscriptionProfile\MessageHistoryLogger;
use TNW\Subscriptions\Model\ResourceModel\ProductBillingFrequency\CollectionFactory as ProductBillingFrequencyCollectionFactory;
use TNW\Subscriptions\Model\ResourceModel\ProductSubscriptionProfile\CollectionFactory as ProductSubscriptionProfileCollectionFactory;

class ClearDbProductDeleteAfter implements ObserverInterface
{
    /**
     * @var $productBillFrequencyCollection
     */
    protected $productBillFrequencyCollection;

    /**
     * @var $productSubscriptionProfileCollection
     */
    protected $productSubscriptionProfileCollection;

    /**
     * @var $magentoProductCollection
     */
    protected $magentoProductCollection;

    /**
     * @var $historyLogger
     */
    protected $historyLogger;

    /**
     * ClearDbProductDeleteAfter constructor.
     * @param ProductBillingFrequencyCollectionFactory $productsBillFrequencyCollection
     * @param ProductSubscriptionProfileCollectionFactory $productsSubscriptionProfileCollection
     * @param MagentoProductCollectionFactory $magentoProductCollection
     * @param MessageHistoryLogger $historyLogger
     */
    public function __construct(
        ProductBillingFrequencyCollectionFactory $productsBillFrequencyCollection,
        ProductSubscriptionProfileCollectionFactory $productsSubscriptionProfileCollection,
        MagentoProductCollectionFactory $magentoProductCollection,
        MessageHistoryLogger $historyLogger
    ) {
        $this->productBillFrequencyCollection = $productsBillFrequencyCollection;
        $this->productSubscriptionProfileCollection = $productsSubscriptionProfileCollection;
        $this->magentoProductCollection = $magentoProductCollection;
        $this->historyLogger = $historyLogger;
    }

    /**
     * Clear product subscribe profile and product billing frequency table after delete product and add logging
     *
     * @param Observer $observer
     * @throws CouldNotDeleteException
     */
    public function execute(Observer $observer)
    {
        $entity = $observer->getEvent()->getEntity();
        $product = $this->magentoProductCollection->create()->getItemsByColumnValue('entity_id', $entity->getEntityId());
        if (!$product) {
            try {
                $productSubscriptions = $this->productSubscriptionProfileCollection->create()->getItemsByColumnValue(
                    'magento_product_id',
                    $entity->getEntityId()
                );
                foreach ($productSubscriptions as $item) {
                    $this->historyLogger->log(
                        __('Product %1 has been removed.', $item->getName()),
                        $item->getSubscriptionProfileId()
                    );
                    $item->delete();
                }
                $productsBillFrequency = $this->productBillFrequencyCollection->create()->getItemsByColumnValue(
                    'magento_product_id',
                    $entity->getEntityId()
                );
                foreach ($productsBillFrequency as $item) {
                    $item->delete();
                }
            } catch (\Exception $exception) {
                throw new CouldNotDeleteException(__($exception->getMessage()));
            }
        }
    }
}
