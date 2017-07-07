<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Trial length unit type attribute and configuration data source.
 */
class TrialLengthUnitType extends AbstractSource
{
    /**#@+
     * Constants for unit type.
     */
    const MINUTES = 1;
    const HOURS = 2;
    const DAYS = 3;
    const WEEKS = 4;
    const MONTHS = 5;
    const YEARS = 6;
    /**#@-*/

    /**
     * Get options for TrialLengthUnit Type.
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::MINUTES,
                'label' => __('Minutes'),
            ],
            [
                'value' => self::HOURS,
                'label' => __('Hours'),
            ],
            [
                'value' => self::DAYS,
                'label' => __('Days'),
            ],
            [
                'value' => self::WEEKS,
                'label' => __('Weeks'),
            ],
            [
                'value' => self::MONTHS,
                'label' => __('Months'),
            ],
            [
                'value' => self::YEARS,
                'label' => __('Years'),
            ],
        ];

        return $optionList;
    }

    /**
     * Get trial length unit label by value.
     *
     * @param int $value
     * @return string
     */
    public function getLabelByValue($value)
    {
        $label = '';
        foreach ($this->getAllOptions() as $option) {
            if ($value === $option['value']) {
                $label = $option['label'];
                break;
            }
        }

        return $label;
    }
}
