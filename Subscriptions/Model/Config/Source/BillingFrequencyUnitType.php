<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Billing Frequency Unit Type configuration data source.
 */
class BillingFrequencyUnitType implements ArrayInterface
{
    /**#@+
     * Constants for Billing Frequency Unit Type.
     */
    const MINUTES = 1;
    const HOURS = 2;
    const DAYS = 3;
    const WEEKS = 4;
    const MONTHS = 5;
    const YEARS = 6;
    /**#@-*/

    /**
     * Get options for Billing Frequency Unit Type Type.
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [
//            [
//                'value' => self::MINUTES,
//                'label' => __('Minute(s)'),
//            ],
//            [
//                'value' => self::HOURS,
//                'label' => __('Hour(s)'),
//            ],
            [
                'value' => self::DAYS,
                'label' => __('Day(s)'),
            ],
//            [
//                'value' => self::WEEKS,
//                'label' => __('Week(s)'),
//            ],
            [
                'value' => self::MONTHS,
                'label' => __('Month(s)'),
            ],
//            [
//                'value' => self::YEARS,
//                'label' => __('Year(s)'),
//            ],
        ];

        return $optionList;
    }
}
