<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Discount Type attribute and configuration data source.
 */
class DiscountType extends AbstractSource
{
    /**
     * Constants for Discount Type.
     */
    const FLAT_FEE_DISCOUNT_TYPE = 1;
    const PERCENT_DISCOUNT_TYPE = 2;

    /**
     * @inheritdoc
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
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
