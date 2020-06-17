<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment;

use \TNW\Subscriptions\Model\Config as SubscriptionConfig;
use \TNW\Subscriptions\Model\SubscriptionProfile\Manager;

/**
 * Class DataBuilder
 * @package TNW\Subscriptions\Model\Payment
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
     * @param \Magento\Quote\Model\Quote $order
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Json_Exception
     */
    public function getAmount($order)
    {
        if ($this->subscriptionConfig->isStaticTrialAuth($order->getStoreId())) {
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
            $profile = $this->manager->getProfile();
            $products = $profile->getProfileProducts();
            $amount = 0;
            foreach ($products as $product) {
                $amount += (float) $product->getPrice();
            }
            $result = $amount;
        }
        return $result;
    }
}
