<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer\SalesOrderLoadAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ExtensionAttributes implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessor
     */
    private $joinProcessor;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessor $joinProcessor
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->joinProcessor = $joinProcessor;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $dataObject = $observer->getData('data_object');
        if (!$dataObject instanceof \Magento\Sales\Model\Order) {
            return;
        }

        if ($dataObject->getExtensionAttributes()) {
            return;
        }

        $collection = $this->collectionFactory->create()
            ->addFieldToFilter($dataObject->getIdFieldName(), $dataObject->getId());

        $this->joinProcessor->process($collection);
        $dataAll = $collection->getData();

        if (empty($dataAll[0][$dataObject::EXTENSION_ATTRIBUTES_KEY])) {
            return;
        }

        $dataObject->setExtensionAttributes($dataAll[0][$dataObject::EXTENSION_ATTRIBUTES_KEY]);
    }
}
