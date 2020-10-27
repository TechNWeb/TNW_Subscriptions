<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Class PriceStrategy
 * @package TNW\Subscriptions\Model\Config\Source
 */
class PriceStrategy extends AbstractSource
{
    /**
     * Grandfathered Price value
     */
    const GRANDFATHERED_PRICE = 0;

    /**
     * Dynamic Price value
     */
    const DYNAMIC_PRICE = 1;

    /**
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::GRANDFATHERED_PRICE,
                'label' => __('Grandfathered Price'),
            ],
            [
                'value' => self::DYNAMIC_PRICE,
                'label' => __('Dynamic (for any quantity changes)'),
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
