<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class LoadExtensionAttributes - observer
 */
class LoadExtensionAttributes implements ObserverInterface
{
    /**
     * @var \TNW\Subscriptions\Model\ResourceModel\ExtensionAttributes
     */
    private $extensionAttributes;

    /**
     * LoadExtensionAttributes constructor.
     * @param \TNW\Subscriptions\Model\ResourceModel\ExtensionAttributes $extensionAttributes
     */
    public function __construct(
        \TNW\Subscriptions\Model\ResourceModel\ExtensionAttributes $extensionAttributes
    ) {
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\Model\AbstractExtensibleModel $dataObject */
        $dataObject = $observer->getData('data_object');
        $this->extensionAttributes->load($dataObject);
    }
}
