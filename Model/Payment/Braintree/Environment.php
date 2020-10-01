<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Payment\Braintree;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Environment - braintree
 */
class Environment implements ArrayInterface
{
    /**
     * Production Environment value
     */
    const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * Sandbox Environment value
     */
    const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ENVIRONMENT_SANDBOX,
                'label' => 'Sandbox',
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => 'Production'
            ]
        ];
    }
}
