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

    const PLURAL = "s";

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
                'label' => 'Day',
            ],
//            [
//                'value' => self::WEEKS,
//                'label' => __('Week(s)'),
//            ],
            [
                'value' => self::MONTHS,
                'label' => 'Month',
            ],
//            [
//                'value' => self::YEARS,
//                'label' => __('Year(s)'),
//            ],
        ];

        return $optionList;
    }

    /**
     * Get label by value depends on frequency.
     *
     * @param int $value
     * @param int $frequency
     * @return null|string
     */
    private function getLabelByValueAndFrequency($value, $frequency)
    {
        $result = null;

        foreach ($this->toOptionArray() as $option){
            if ($option['value'] == $value){
                $result = $option['label'];
                if ($frequency != 1) {
                    $result = $result . self::PLURAL;
                }
            }
        }

        return __($result);
    }

    /**
     * Get label with frequency + label.
     *
     * @param int $unit
     * @param int $frequency
     * @return string
     */
    public function getPeriodLabel($unit, $frequency)
    {
        $label = $this->getLabelByValueAndFrequency($unit, $frequency);

        return $frequency . ' ' . $label;
    }
}
