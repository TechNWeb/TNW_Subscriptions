<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class LoadCollectionExtensionAttributes implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttribute\JoinProcessor
     */
    private $joinProcessor;

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

        $collection = reset($collectionIndex);
        $this->joinProcessor->process($collection);
    }
}
