<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Checkout\ConfigProvider;

use Magento\Payment\Model\CcConfigProvider;
use Magento\Tax\Model\Config as TaxConfig;
use TNW\Subscriptions\Model\Checkout\ConfigProviderInterface;

/**
 * Payment config for Cart.
 */
class Payment implements ConfigProviderInterface
{
    /**
     * @var CcConfigProvider
     */
    private $ccConfigProvider;

    /**
     * @param CcConfigProvider $ccConfigProvider
     */
    public function __construct(

        CcConfigProvider $ccConfigProvider
    ) {
        $this->ccConfigProvider = $ccConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'paymentMethods' => [],
            'reloadOnBillingAddress' => false,
            'payment' => [
                'ccform' => [
                    'icons' => $this->ccConfigProvider->getIcons()
                ]
            ]
        ];
    }
}
