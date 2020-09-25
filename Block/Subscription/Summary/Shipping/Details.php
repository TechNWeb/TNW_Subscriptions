<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Summary\Shipping;

use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Block\Subscription\Summary\BaseSummary;

/**
 *  Class for subscription profile summary shipping details block on frontend Customer Account.
 */
class Details extends BaseSummary
{
    /**
     * Path to template file in theme.
     *
     * @var string
     */
    protected $_template = 'TNW_Subscriptions::subscription_profile/summary/overview/shipping-details.phtml';

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * Details constructor.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        Template\Context $context,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    /**
     * Return current profile shipping method description
     *
     * @return string|null
     */
    public function getShippingDescription()
    {
        return $this->getSubscriptionProfile()
            ? $this->getSubscriptionProfile()->getShippingDescription()
            : '';
    }

    /**
     * Return current profile shipping amount
     *
     * @return string
     */
    public function getShippingAmount()
    {
        $shippingAmount = '';
        if ($this->getSubscriptionProfile()) {
            $shippingAmount = $this->priceCurrency->getCurrency()->format(
                $this->getSubscriptionProfile()->getShipping(),
                [],
                2,
                null,
                $this->getSubscriptionProfile()->getProfileCurrencyCode()
            );
        }
        return $shippingAmount;
    }

    /**
     * @inheritdoc
     */
    public function getEditUrl($tabName = 'shipment', array $params = [])
    {
        $params['shipping_details'] = 1;

        return parent::getEditUrl($tabName, $params);
    }

    /**
     * Return Tab name.
     *
     * @return string
     */
    public function getTabName()
    {
        return 'shipping';
    }

    /**
     * Check if only table should be rendered
     *
     * @return bool
     */
    public function isOnlyTableContent()
    {
        return (bool)$this->getOnlyTableContent();
    }
}
