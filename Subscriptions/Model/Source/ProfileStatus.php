<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Profile status data source.
 */
class ProfileStatus implements OptionSourceInterface
{
    /**#@+
     * Constants for profile status.
     */
    const STATUS_CANCELED = 0;   //Profile is canceled.
    const STATUS_ACTIVE = 1;     //Profile is active.
    const STATUS_PENDING = 2;    //Profile is created but have not yet created an order.
    const STATUS_TRIAL = 3;      //Profile is in trial period.
    const STATUS_HOLDED = 4;     //Profile does not creates orders.
    const STATUS_SUSPENDED = 5;  //Profile Ccn not create an order and grace period is ended.
    /**#@-*/

    /**
     * Returns options for profile status.
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var array $optionList */
        $optionList = [
            [
                'value' => self::STATUS_CANCELED,
                'label' => __('Canceled'),
            ],
            [
                'value' => self::STATUS_PENDING,
                'label' => __('Pending'),
            ],
            [
                'value' => self::STATUS_ACTIVE,
                'label' => __('Active'),
            ],
            [
                'value' => self::STATUS_HOLDED,
                'label' => __('Holded'),
            ],
            [
                'value' => self::STATUS_SUSPENDED,
                'label' => __('Suspended'),
            ],
            [
                'value' => self::STATUS_TRIAL,
                'label' => __('Trial'),
            ],
        ];

        return $optionList;
    }


    /**
     * Retrieve option array.
     *
     * @return string[]
     */
    public function toOptionArray()
    {
        return [
            self::STATUS_CANCELED => __('Canceled'),
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_HOLDED => __('Holded'),
            self::STATUS_SUSPENDED => __('Suspended'),
            self::STATUS_TRIAL => __('Trial'),
        ];
    }

    /**
     * Returns status label by value.
     *
     * @param $value
     * @return null
     */
    public function getLabelByValue($value)
    {
        $result = null;

        foreach ($this->getAllOptions() as $option){
            if ($option['value'] == $value){
                $result = $option['label'];
            }
        }

        return $result;
    }
}