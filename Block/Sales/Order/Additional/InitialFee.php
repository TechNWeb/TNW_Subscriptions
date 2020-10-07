<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Sales\Order\Additional;

/**
 * Class InitialFee - block for initial fee on sales order additional
 */
class InitialFee extends \Magento\Framework\View\Element\Template
{
    /**
     * @return bool|string
     */
    public function getInitialFee()
    {
        $item = $this->getOrder()->getItemById($this->getItem()->getItemId());
        if (!$item instanceof \Magento\Sales\Model\Order\Item) {
            return 0;
        }

        $extensionAttributes = $item->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Sales\Api\Data\OrderItemExtensionInterface) {
            return 0;
        }

        $initialFees = $extensionAttributes->getSubsInitialFees();
        if (!$initialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem) {
            return 0;
        }

        return $initialFees->getSubsInitialFee();
    }

    /**
     * @return \Magento\Sales\Model\Order\Item
     */
    private function getItem()
    {
        return $this->getParentBlock()->getData('item');
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getItem()->getOrder();
    }
}
