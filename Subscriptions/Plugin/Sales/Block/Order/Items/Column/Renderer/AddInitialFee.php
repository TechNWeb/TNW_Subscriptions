<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Block\Order\Items\Column\Renderer;

use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;

class AddInitialFee
{
    /**
     * @param DefaultRenderer $subject
     * @param array $result
     * @return array
     */
    public function afterGetColumns(DefaultRenderer $subject, $result)
    {
        $pos   = array_search('total', array_keys($result));
        $result = array_merge(
            array_slice($result, 0, $pos),
            ['tnw_subscriptions_initial_fee' => 'tnw-subscriptions-initial-fee'],
            array_slice($result, $pos)
        );

        return $result;
    }
}