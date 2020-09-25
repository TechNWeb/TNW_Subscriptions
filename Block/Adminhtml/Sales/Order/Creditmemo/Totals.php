<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Class Totals - block class for totals block
 */
class Totals extends Template
{
    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get totals source object
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Create subscriptions initial fee totals summary
     *
     * @return $this
     */
    public function initTotals()
    {
        $extensionAttributes = $this->getSource()->getExtensionAttributes();
        if (!$extensionAttributes instanceof \Magento\Sales\Api\Data\CreditmemoExtensionInterface) {
            return $this;
        }

        $initialFeeTotal = $extensionAttributes->getSubscriptionInitialFee();
        $initialFeeBaseTotal = $extensionAttributes->getBaseSubscriptionInitialFee();

        if ($initialFeeTotal) {
            // Add our total information to the set of other totals
            $total = new DataObject(
                [
                    'code' => $this->getNameInLayout(),
                    'label' => __('Initial Fee(s)'),
                    'value' => $initialFeeTotal,
                    'base_value' => $initialFeeBaseTotal
                ]
            );
            if ($this->getBeforeCondition()) {
                $this->getParentBlock()->addTotalBefore($total, $this->getBeforeCondition());
            } else {
                $this->getParentBlock()->addTotal($total, $this->getAfterCondition());
            }
        }

        return $this;
    }
}
