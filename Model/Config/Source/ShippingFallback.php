<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Config\Source;

/**
 * Class ShippingFallback - config source model
 */
class ShippingFallback implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Value for cheapest shipping method
     */
    const CHEAPEST = 2;

    /**
     * Value for default shipping method
     */
    const DEFAULT_VALUE = 3;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->toArray() as $value => $label) {
            $result[] =  [
                'value' => $value,
                'label' => $label
            ];
        }
        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::CHEAPEST => __('Pick the cheapest available'),
            self::DEFAULT_VALUE =>  __('Use the Default')
        ];
    }
}
