<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;


/**
 * Class StartDateType
 *
 * @package TNW\Subscriptions\Model\Config\Source
 */
class StartDateType implements ArrayInterface
{

    const MOMENT_OF_PURCHASE = 1;
    const DEFINED_BY_CUSTOMER = 2;
    const LAST_DAY_OF_THE_CURRENT_MONTH = 3;

    /**
     * get options for StartDate Type
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::MOMENT_OF_PURCHASE,
                'label' => 'Moment of purchase',
            ],
            [
                'value' => self::DEFINED_BY_CUSTOMER,
                'label' => 'Defined by customer',
            ],
            [
                'value' => self::LAST_DAY_OF_THE_CURRENT_MONTH,
                'label' => 'Last day of the current month',
            ],
        ];

        return $optionList;
    }
}
