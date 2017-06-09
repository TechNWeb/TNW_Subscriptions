<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Discount Type attribute and configuration data source.
 */
class DiscountType extends AbstractSource
{
    /**#@+
     * Constants for Discount Type.
     */
    const FLAT_FEE_DISCOUNT_TYPE = 1;
    const PERCENT_DISCOUNT_TYPE = 2;
    /**#@-*/

    /**
     * Get options for Discount Type.
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::FLAT_FEE_DISCOUNT_TYPE,
                'label' => __('Flat Fee'),
            ],
            [
                'value' => self::PERCENT_DISCOUNT_TYPE,
                'label' => __('Percent'),
            ],
        ];

        return $optionList;
    }
}
