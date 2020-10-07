<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Sales\Order;

use Magento\Framework\View\Element\Template;

/**
 * Class InitialFee - block to display the initial fee on order
 */
class InitialFee extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    private $dataObjectFactory;

    /**
     * InitialFee constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Initialize all order totals relates with initial_fee
     *
     * @return InitialFee
     */
    public function initTotals()
    {
        if (!$this->calculateInitialFee()) {
            return $this;
        }

        $taxTotal = $this->dataObjectFactory->create([
            'code' => 'initial_fee',
            'block_name' => $this->getNameInLayout()
        ]);

        $this->getParentBlock()->addTotal($taxTotal, 'tax');

        return $this;
    }

    /**
     * @return float
     */
    public function calculateInitialFee()
    {
        $source = $this->getSource();

        switch (true) {
            case $source instanceof \Magento\Sales\Model\Order:
                return array_reduce($source->getAllVisibleItems(), [$this, 'reduceOrderItems']);

            case $source instanceof \Magento\Sales\Model\Order\Invoice:
                return array_reduce($source->getAllItems(), [$this, 'reduceInvoiceItems']);
        }

        return 0;
    }

    /**
     * @param $carry
     * @param \Magento\Sales\Model\Order\Item $item
     *
     * @return float
     */
    private function reduceOrderItems($carry, \Magento\Sales\Model\Order\Item $item)
    {
        $extensionAttributes = $item->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Sales\Api\Data\OrderItemExtensionInterface) {
            return $carry;
        }

        $initialFees = $extensionAttributes->getSubsInitialFees();
        if (!$initialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem) {
            return $carry;
        }

        $carry += $initialFees->getSubsInitialFee() * $item->getQtyOrdered();
        return $carry;
    }

    /**
     * @param $carry
     * @param \Magento\Sales\Model\Order\Invoice\Item $item
     *
     * @return float
     */
    private function reduceInvoiceItems($carry, \Magento\Sales\Model\Order\Invoice\Item $item)
    {
        $extensionAttributes = $item->getOrderItem()->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Sales\Api\Data\OrderItemExtensionInterface) {
            return $carry;
        }

        $initialFees = $extensionAttributes->getSubsInitialFees();
        if (!$initialFees instanceof \TNW\Subscriptions\Model\Sales\ExtensionAttributes\OrderItem) {
            return $carry;
        }

        $carry += $initialFees->getSubsInitialFee() * $item->getQty();
        return $carry;
    }

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return array
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * @return array
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }
}
