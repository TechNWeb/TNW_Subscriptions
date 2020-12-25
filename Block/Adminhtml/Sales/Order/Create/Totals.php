<?php

namespace TNW\Subscriptions\Block\Adminhtml\Sales\Order\Create;

class Totals extends \Magento\Sales\Block\Adminhtml\Order\Create\Totals\DefaultTotals
{
    /**
     * Subscription Initial Fee on create order/reorder will always be null.
     * @return string
     */
    protected function _toHtml()
    {
        return '';
    }
}
