<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class SalesOrderCreditmemoRegisterBefore implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $observer->getData('creditmemo');

        /** @var array $input */
        $input = $observer->getData('input');

        if (!isset($input['subscription_initial_fee'])) {
            return;
        }

        $amount = trim($input['subscription_initial_fee']);

        /** @var \Magento\Sales\Api\Data\CreditmemoExtensionInterface $extensionAttributes */
        $extensionAttributes = $creditmemo->getExtensionAttributes();

        $amount = $this->priceCurrency->round($amount);
        $extensionAttributes->setBaseSubscriptionInitialFee($amount);

        $amount = $this->priceCurrency->round($amount * $creditmemo->getOrder()->getBaseToOrderRate());
        $extensionAttributes->setSubscriptionInitialFee($amount);
    }
}
