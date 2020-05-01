<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Config\Source;

/**
 * Class FreeShipping
 * @package TNW\Subscriptions\Model\Config\Source
 */
class FreeShipping implements \Magento\Framework\Option\ArrayInterface
{
    const HONOR_MAGENTO_VALUE = 1;

    const ALTERNATE_VALUE = 2;

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
            self::HONOR_MAGENTO_VALUE => __('Honor Magento free shipping'),
            self::ALTERNATE_VALUE =>  __('Substitute with an alternate')
        ];
    }
}
