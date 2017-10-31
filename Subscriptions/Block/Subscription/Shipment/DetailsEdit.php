<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Shipment;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\Source\ShippingMethods as ShippingMethodsSource;
use TNW\Subscriptions\Model\SubscriptionProfile;
use TNW\Subscriptions\Model\SubscriptionProfile\Manager as ProfileManager;

/**
 * Customer address details edit block
 */
class DetailsEdit extends \Magento\Framework\View\Element\Template
{
    /**
     * Registry model
     *
     * @var Registry
     */
    private $registry;

    /**
     * Shipping methods source
     *
     * @var ShippingMethodsSource
     */
    private $shippingMethods;

    /**
     * @var ProfileManager
     */
    private $profileManager;

    /**
     * @param Registry $registry
     * @param Context $context
     * @param ShippingMethodsSource $shippingMethods
     * @param ProfileManager $profileManager
     * @param array $data
     */
    public function __construct(
        Registry $registry,
        Context $context,
        ShippingMethodsSource $shippingMethods,
        ProfileManager $profileManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->profileManager = $profileManager;
        $this->registry = $registry;
        $this->shippingMethods = $shippingMethods;
    }

    /**
     * Retrieve current subscription model instance.
     *
     * @return SubscriptionProfile
     */
    private function getSubscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }

    /**
     * Returns shipping methods as array
     *
     * @return array
     */
    public function getShippingMethodsArray()
    {
        $options = [];
        if ($this->getSubscriptionProfile()) {
            $this->profileManager->setProfile($this->getSubscriptionProfile());
            $nextQuote = $this->profileManager->getNextQuote();
        if ($nextQuote) {
            $options = $this->shippingMethods->getFormattedShippingMethodOptions($nextQuote);
        }
        }

        return $options;
    }

    /**
     * Check if $shippingMethodName is subscription profile shipping method
     *
     * @param string $optionValue
     * @return string
     */
    public function isSubscriptionShippingMethod($shippingMethodName)
    {
        return ($shippingMethodName === $this->getSubscriptionProfile()->getShippingMethod())? 'checked':'';
    }
}
