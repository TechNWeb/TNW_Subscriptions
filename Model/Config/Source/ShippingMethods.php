<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Config\Source;

/**
 * Class ShippingMethods
 * @package TNW\Subscriptions\Model\Config\Source
 */
class ShippingMethods implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Shipping\Model\Config\Source\Allmethods
     */
    private $shippingMethodsSource;

    /**
     * ShippingMethods constructor.
     * @param \Magento\Shipping\Model\Config\Source\Allmethods $shippingMethodsSource
     */
    public function __construct(
        \Magento\Shipping\Model\Config\Source\Allmethods $shippingMethodsSource
    ) {
        $this->shippingMethodsSource = $shippingMethodsSource;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->shippingMethodsSource->toOptionArray(true);
    }
}
