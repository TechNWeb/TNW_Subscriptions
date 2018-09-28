<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Payment\Model\Checks;

class CanUseCheckout
{
    /**
     * @var \TNW\Subscriptions\Model\Config
     */
    private $config;

    /**
     * CanUseCheckout constructor.
     *
     * @param \TNW\Subscriptions\Model\Config $config
     */
    public function __construct(
        \TNW\Subscriptions\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param \Magento\Payment\Model\Checks\CanUseCheckout $subject
     * @param callable $callback
     * @param \Magento\Payment\Model\MethodInterface $paymentMethod
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return bool
     */
    public function aroundIsApplicable(
        \Magento\Payment\Model\Checks\CanUseCheckout $subject,
        callable $callback,
        \Magento\Payment\Model\MethodInterface $paymentMethod,
        \Magento\Quote\Model\Quote $quote
    ) {
        if (!$callback($paymentMethod, $quote)) {
            return false;
        }

        if ($quote->hasData('is_tnw_subscription') && $quote->getData('is_tnw_subscription')) {
            return $this->config->isPaymentAvailable($paymentMethod->getCode(), $quote->getStore()->getWebsiteId());
        }

        return true;
    }
}
