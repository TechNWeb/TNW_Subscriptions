<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class LoadCollectionExtensionAttributes - observer
 */
class LoadCollectionExtensionAttributes implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessor
     */
    private $joinProcessor;

    /**
     * LoadCollectionExtensionAttributes constructor.
     * @param \Magento\Framework\Api\ExtensionAttribute\JoinProcessor $joinProcessor
     */
    public function __construct(
        \Magento\Framework\Api\ExtensionAttribute\JoinProcessor $joinProcessor
    ) {
        $this->joinProcessor = $joinProcessor;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $collectionIndex = array_filter($observer->getData(), function ($value) {
            return $value instanceof \Magento\Framework\Data\Collection\AbstractDb;
        });

        if (empty($collectionIndex)) {
            return;
        }

        /** @var \Magento\Framework\Data\Collection\AbstractDb $collection */
        $collection = reset($collectionIndex);
        $this->joinProcessor->process($collection);
        //$collection->getSelect()->group($collection->getResource()->getIdFieldName());
    }
}
