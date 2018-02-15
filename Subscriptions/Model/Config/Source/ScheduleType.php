<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Schedule Type attribute and configuration data source.
 */
class ScheduleType extends AbstractSource
{
    /**
     * Constants for Schedule Type.
     */
    const USE_DEFINED_BILLING_FREQUENCIES = 1;
    const DEFINED_BY_CUSTOMER = 2;

    /**
     * Get options for Schedule Type.
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::USE_DEFINED_BILLING_FREQUENCIES,
                'label' => __('Use defined billing frequencies'),
            ],
            [
                'value' => self::DEFINED_BY_CUSTOMER,
                'label' => __('Defined by the customer'),
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
