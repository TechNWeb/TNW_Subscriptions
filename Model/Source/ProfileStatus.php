<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
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
    const STATUS_CANCELED = 0;   // Profile is canceled.
    const STATUS_ACTIVE = 1;     // Profile is active.
    const STATUS_PENDING = 2;    // Profile is created but have not yet created an order.
    const STATUS_TRIAL = 3;      // Profile is in trial period.
    const STATUS_HOLDED = 4;     // Profile does not creates orders.
    const STATUS_SUSPENDED = 5;  // Profile can not create an order and grace period is ended.
    const STATUS_COMPLETE = 6;   // Profile is successfully completed.
    const STATUS_PAST_DUE = 7;   // Profile can not create an order and grace period is not ended.
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
                'label' => __('On Hold'),
            ],
            [
                'value' => self::STATUS_SUSPENDED,
                'label' => __('Suspended'),
            ],
            [
                'value' => self::STATUS_TRIAL,
                'label' => __('Trial'),
            ],
            [
                'value' => self::STATUS_COMPLETE,
                'label' => __('Complete'),
            ],
            [
                'value' => self::STATUS_PAST_DUE,
                'label' => __('Past Due'),
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
            self::STATUS_HOLDED => __('On Hold'),
            self::STATUS_SUSPENDED => __('Suspended'),
            self::STATUS_TRIAL => __('Trial'),
            self::STATUS_COMPLETE => __('Complete'),
            self::STATUS_PAST_DUE => __('Past Due'),
        ];
    }

    /**
     * Returns status label by value.
     *
     * @param $value
     * @return string|null
     */
    public function getLabelByValue($value)
    {
        $options = $this->toOptionArray();
        if (!isset($options[$value])) {
            return null;
        }

        return $options[$value];
    }
}
