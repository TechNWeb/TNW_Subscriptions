<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;


/**
 * Class DiscountType
 *
 * @package TNW\Subscriptions\Model\Config\Source
 */
class DiscountType extends AbstractSource
{

    const FLAT_FEE_DISCOUNT_TYPE = 1;
    const PERCENT_DISCOUNT_TYPE = 2;


    /**
     * get options for Discount Type
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::FLAT_FEE_DISCOUNT_TYPE,
                'label' => 'Flat Fee',
            ],
            [
                'value' => self::PERCENT_DISCOUNT_TYPE,
                'label' => 'Percent',
            ],
        ];

        return $optionList;
    }
}
