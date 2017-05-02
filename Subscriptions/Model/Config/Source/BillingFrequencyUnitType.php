<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;


/**
 * Class BillingFrequencyUnitType
 *
 * @package TNW\Subscriptions\Model\Config\Source
 */
class BillingFrequencyUnitType implements ArrayInterface
{

    const MINUTES = 1;
    const HOURS = 2;
    const DAYS = 3;
    const WEEKS = 4;
    const MONTHS = 5;
    const YEARS = 6;

    /**
     * get options for BillingFrequencyUnit Type
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [
//            [
//                'value' => self::MINUTES,
//                'label' => 'Minute(s)',
//            ],
//            [
//                'value' => self::HOURS,
//                'label' => 'Hour(s)',
//            ],
            [
                'value' => self::DAYS,
                'label' => 'Day(s)',
            ],
//            [
//                'value' => self::WEEKS,
//                'label' => 'Week(s)',
//            ],
            [
                'value' => self::MONTHS,
                'label' => 'Month(s)',
            ],
//            [
//                'value' => self::YEARS,
//                'label' => 'Year(s)',
//            ],
        ];

        return $optionList;
    }
}
