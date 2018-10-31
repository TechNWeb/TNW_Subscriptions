<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PaymentMethodIsActive implements ObserverInterface
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
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\DataObject $checkResult */
        $checkResult = $observer->getData('result');
        if (!$checkResult->getData('is_available')) {
            return;
        }

        /** @var \Magento\Quote\Api\Data\PaymentMethodInterface $paymentMethod */
        $paymentMethod = $observer->getData('method_instance');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return;
        }

        if ($quote->hasData('is_tnw_subscription') && $quote->getData('is_tnw_subscription')) {
            $checkResult->setData(
                'is_available',
                $this->config->isPaymentAvailable($paymentMethod->getCode(), $quote->getStore()->getWebsiteId())
            );
        }
    }
}