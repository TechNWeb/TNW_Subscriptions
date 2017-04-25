<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;


/**
 * Class TrialLengthUnitType
 *
 * @package TNW\Subscriptions\Model\Config\Source
 */
class TrialLengthUnitType implements ArrayInterface
{

    const MINUTES = 1;
    const HOURS = 2;
    const DAYS = 3;
    const WEEKS = 4;
    const MONTHS = 5;
    const YEARS = 6;

    /**
     * get options for TrialLengthUnit Type
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::MINUTES,
                'label' => 'Minutes',
            ],
            [
                'value' => self::HOURS,
                'label' => 'Hours',
            ],
            [
                'value' => self::DAYS,
                'label' => 'Days',
            ],
            [
                'value' => self::WEEKS,
                'label' => 'Weeks',
            ],
            [
                'value' => self::MONTHS,
                'label' => 'Months',
            ],
            [
                'value' => self::YEARS,
                'label' => 'Years',
            ],
        ];

        return $optionList;
    }
}
