<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;


/**
 * Class DiscountType
 *
 * @package TNW\Subscriptions\Model\Config\Source
 */
class DiscountType implements ArrayInterface
{

    const FLAT_FEE_DISCOUNT_TYPE = 1;
    const PERCENT_DISCOUNT_TYPE = 2;

    /**
     * get options for Discount Type
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::FLAT_FEE_DISCOUNT_TYPE,
                'label' => 'Flat Fee',
            ],
            [
                'value' => self::PERCENT_DISCOUNT_TYPE,
                'label' => 'Percent',
            ],
        ];

        return $optionList;
    }
}
