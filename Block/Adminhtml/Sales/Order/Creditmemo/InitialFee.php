<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\Creditmemo;

/**
 * Subscription initial fee block for credit memo.
 */
class InitialFee extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * InitialFee constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Source object
     *
     * @var \Magento\Sales\Model\Order\Creditmemo
     */
    private $source;

    /**
     * Initialize creditmemo totals.
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->source = $parent->getSource();
        $total = new \Magento\Framework\DataObject([
            'code' => 'tnw_subs_initial_fee',
            'block_name' => $this->getNameInLayout()
        ]);
        $parent->addTotal($total, 'agjustments');

        return $this;
    }

    /**
     * Get source object
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Returns total subscription initial fee for credit memo.
     *
     * @return float|int
     */
    public function getInitialFeeAmount()
    {
        $initialFee = $this->getSource()->getExtensionAttributes()->getBaseSubscriptionInitialFee();
        return $this->priceCurrency->round($initialFee) * 1;
    }

    /**
     * Get label for shipping total based on configuration settings
     *
     * @return string
     */
    public function getInitialFeeLabel()
    {
        return __('Initial Fee(s)');
    }
}
