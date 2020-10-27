<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveExtensionAttributes implements ObserverInterface
{
    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\ExtensionAttributes
     */
    private $extensionAttributes;

    public function __construct(
        \TNW\Subscriptions\Model\ResourceModel\ExtensionAttributes $extensionAttributes
    ) {
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\Model\AbstractExtensibleModel $dataObject */
        $dataObject = $observer->getData('data_object');
        $this->extensionAttributes->save($dataObject);
    }
}
