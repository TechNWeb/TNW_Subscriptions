<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment;

use TNW\Subscriptions\Model\Config as SubscriptionConfig;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager;
use Magento\Quote\Model\Quote;

/**
 * Class DataBuilder - base payment data builder
 */
class DataBuilder
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var SubscriptionConfig
     */
    protected $subscriptionConfig;

    /**
     * @var bool
     */
    protected $is3DSecure = false;

    /**
     * DataBuilder constructor.
     * @param SubscriptionConfig $subscriptionConfig
     * @param Manager $manager
     */
    public function __construct(
        SubscriptionConfig $subscriptionConfig,
        Manager $manager
    ) {
        $this->manager = $manager;
        $this->subscriptionConfig = $subscriptionConfig;
    }

    /**
     * @param $order
     * @return float|int|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \TNW\Subscriptions\Model\SubscriptionProfile\Engine\InvalidEngineException
     */
    public function getAmount($order)
    {
        if ($this->subscriptionConfig->isStaticTrialAuth($order->getStoreId()) && !$this->is3DSecure) {
            $result = $this->subscriptionConfig->getStaticAuthAmount($order->getStoreId());
        } else {
            $subscriptionItems = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $option = $item->getOptionByCode('subscription');
                if (null !== $option) {
                    $subscriptionItems[] = $item;
                }
            }
            if ($subscriptionItems) {
                $this->manager->populateProfileData($order, $subscriptionItems);
            }
            if ($order instanceof Quote) {
                $result = $this->getAmountByProfile($this->manager->getProfile(), $order);
            } else {
                $result = $this->getAmountByProfile($this->manager->getProfile());
            }
        }
        return $result;
    }

    /**
     * @param $profile
     * @param null $quote
     * @return float|int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \TNW\Subscriptions\Model\SubscriptionProfile\Engine\InvalidEngineException
     */
    public function getAmountByProfile($profile, $quote = null)
    {
        $amount = 0;
        if ($profile) {
            if ($quote && !$quote->getPayment()->getMethod()) {
                $tempQuote = $quote;
            } else {
                $tempQuote = $this->manager->getTempQuote($profile);
            }
            $amount = $tempQuote->getGrandTotal();
        }
        return $amount;
    }
}
