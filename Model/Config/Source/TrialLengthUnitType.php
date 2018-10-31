<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Trial length unit type attribute and configuration data source.
 */
class TrialLengthUnitType extends AbstractSource
{
    /**
     * Constants for unit type.
     */
    const MINUTES = 1;
    const HOURS = 2;
    const DAYS = 3;
    const WEEKS = 4;
    const MONTHS = 5;
    const YEARS = 6;

    /**
     * Plural prefix.
     */
    const PLURAL = "s";

    /**
     * Get options for TrialLengthUnit Type.
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
/*            [
                'value' => self::MINUTES,
                'label' => __('Minutes'),
            ],
            [
                'value' => self::HOURS,
                'label' => __('Hours'),
            ],*/
            [
                'value' => self::DAYS,
                'label' => __('Day'),
            ],
/*            [
                'value' => self::WEEKS,
                'label' => __('Weeks'),
            ],*/
            [
                'value' => self::MONTHS,
                'label' => __('Month'),
            ],
/*            [
                'value' => self::YEARS,
                'label' => __('Years'),
            ],*/
        ];

        return $optionList;
    }

    /**
     * Get trial length unit label by value depends on length.
     *
     * @param int $value
     * @param int $length
     *
     * @return string
     */
    public function getLabelByValueAndLength($value, $length)
    {
        $label = '';
        foreach ($this->getAllOptions() as $option) {
            if ($value === $option['value']) {
                $label = $option['label'];
                 if ($length != 1) {
                     $label = $label . self::PLURAL;
                 }
                break;
            }
        }

        return $label;
    }

    /**
     * @inheritdoc
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $type = \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER;
        $columns[$attributeCode] = [
            'type' => $type,
            'length' => null,
            'unsigned' => false,
            'nullable' => true,
            'default' => null,
            'extra' => null,
            'comment' => $attributeCode . ' column',
        ];

        return $columns;
    }
}
