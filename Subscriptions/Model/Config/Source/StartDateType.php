<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Start date type attribute and configuration data source.
 */
class StartDateType extends AbstractSource
{
    /**#@+
     * Constants for start date type.
     */
    const MOMENT_OF_PURCHASE = 1;
    const DEFINED_BY_CUSTOMER = 2;
    const LAST_DAY_OF_THE_CURRENT_MONTH = 3;
    /**#@-*/

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
                'label' => __('Last day of the current month'),
            ],
        ];

        return $optionList;
    }
}
