<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options for Cancel button popup.
 */
class CancelButtonPopupOptions implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $result =
            [
                [
                    'label' => __('Yes, cancel effective immediately.'),
                    'value' => 'now'
                ],
                [
                    'label' => __('Yes, cancel before next billing cycle.'),
                    'value' => 'next'
                ]
            ];


        return $result;
    }
}
