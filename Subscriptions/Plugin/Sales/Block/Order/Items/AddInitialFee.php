<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Block\Order\Items;

use Magento\Sales\Block\Adminhtml\Order\View\Items;

class AddInitialFee
{
    /**
     * @param Items $subject
     * @param array $result
     * @return array
     */
    public function afterGetColumns(Items $subject, $result)
    {
        $pos   = array_search('total', array_keys($result));
        $result = array_merge(
            array_slice($result, 0, $pos),
            ['tnw_subscriptions_initial_fee' => __('Subscription Initial Fee')],
            array_slice($result, $pos)
        );

        return $result;
    }
}