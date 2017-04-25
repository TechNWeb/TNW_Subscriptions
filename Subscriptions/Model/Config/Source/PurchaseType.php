<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */

namespace TNW\Subscriptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;


/**
 * Class PurchaseType
 *
 * @package TNW\Subscriptions\Model\Config\Source
 */
class PurchaseType implements ArrayInterface
{

    const ONE_TIME_PURCHASE_TYPE = 1;
    const RECURRING_PURCHASE_TYPE = 2;
    const ONE_TIME_AND_RECURRING_PURCHASE_TYPE = 3;

    /**
     * get options for Purchase Type
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::ONE_TIME_PURCHASE_TYPE,
                'label' => 'One-Time Purchase Only',
            ],
            [
                'value' => self::RECURRING_PURCHASE_TYPE,
                'label' => 'Recurring Purchase Only',
            ],
            [
                'value' => self::ONE_TIME_AND_RECURRING_PURCHASE_TYPE,
                'label' => 'One-Time and Purchase',
            ],
        ];

        return $optionList;
    }
}
