<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Start date type attribute and configuration data source.
 */
class StartDateType extends AbstractSource
{
    /**
     * Constants for start date type.
     */
    const MOMENT_OF_PURCHASE = 1;
    const DEFINED_BY_CUSTOMER = 2;
    const LAST_DAY_OF_THE_CURRENT_MONTH = 3;
    const FIRST_DAY_OF_THE_MONTH = 4;
    const ON_15TH_OF_THE_MONTH = 5;

    /**
     * Get options for Start date type.
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::MOMENT_OF_PURCHASE,
                'label' => __('Moment of purchase'),
            ],
            [
                'value' => self::DEFINED_BY_CUSTOMER,
                'label' => __('Defined by customer'),
            ],
            [
                'value' => self::LAST_DAY_OF_THE_CURRENT_MONTH,
                'label' => __('On the last day of the month'),
            ],
            [
                'value' => self::FIRST_DAY_OF_THE_MONTH,
                'label' => __('First day of the month'),
            ],
            [
                'value' => self::ON_15TH_OF_THE_MONTH,
                'label' => __('15th of every month'),
            ],
        ];

        return $optionList;
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
