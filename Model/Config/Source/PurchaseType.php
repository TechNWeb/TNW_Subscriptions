<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Purchase Type attribute and configuration data source.
 */
class PurchaseType extends AbstractSource
{
    /**
     * Constants for Purchase Type.
     */
    const ONE_TIME_PURCHASE_TYPE = 1;
    const RECURRING_PURCHASE_TYPE = 2;
    const ONE_TIME_AND_RECURRING_PURCHASE_TYPE = 3;

    /**
     * Get options for Purchase Type.
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::ONE_TIME_PURCHASE_TYPE,
                'label' => __('One-Time Purchase Only'),
            ],
            [
                'value' => self::RECURRING_PURCHASE_TYPE,
                'label' => __('Recurring Purchase Only'),
            ],
            [
                'value' => self::ONE_TIME_AND_RECURRING_PURCHASE_TYPE,
                'label' => __('One-Time and Recurring'),
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
