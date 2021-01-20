<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\DataProvider\SubscriptionProfile\Edit;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options for Suspend button popup.
 */
class SuspendButtonPopupOptions implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $result =
            [
                [
                    'label' => __('Pause indefinitely'),
                    'value' => 'indefinitely'
                ],
                [
                    'label' => __('Pause for: '),
                    'value' => 'billing_cycles'
                ]
            ];

        return $result;
    }
}
