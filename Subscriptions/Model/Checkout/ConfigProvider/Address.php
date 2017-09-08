<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;
use Magento\Directory\Model\Country\Postcode\ConfigInterface as PostcodeConfig;
use Magento\Shipping\Model\Config as ShippingConfig;

/**
 * Address config for Cart.
 */
class Address implements ConfigProviderInterface
{
    /**
     * @var ShippingConfig
     */
    private $shippingConfig;

    /**
     * @var PostcodeConfig
     */
    private $postcodeConfig;

    /**
     * @param ShippingConfig $shippingConfig
     * @param PostcodeConfig $postcodeConfig
     */
    public function __construct(
        ShippingConfig $shippingConfig,
        PostcodeConfig $postcodeConfig
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->postcodeConfig = $postcodeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'activeCarriers' => $this->getActiveCarriers(),
            'postCodes' => $this->postcodeConfig->getPostCodes()
        ];
    }

    /**
     * Get active carrier codes
     *
     * @return array
     */
    private function getActiveCarriers()
    {
        $activeCarriers = [];
        foreach ($this->shippingConfig->getActiveCarriers() as $carrier) {
            $activeCarriers[] = $carrier->getCarrierCode();
        }

        return $activeCarriers;
    }
}
