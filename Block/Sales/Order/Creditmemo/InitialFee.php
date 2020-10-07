<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Sales\Order\Creditmemo;

use Magento\Framework\View\Element\Template;

/**
 * Class InitialFee - block to disaplay initial fee on creditmemo
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
        if (!$source instanceof \Magento\Sales\Model\Order\Creditmemo) {
            return 0;
        }

        $extensionAttributes = $source->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Sales\Api\Data\CreditmemoExtensionInterface) {
            return 0;
        }

        return $extensionAttributes->getSubscriptionInitialFee();
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
