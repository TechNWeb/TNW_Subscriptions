<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Block\Order\Items;

use Magento\Sales\Block\Adminhtml\Order\View\Items;
use TNW\Subscriptions\Model\Context;

/**
 * Class AddInitialFee
 */
class AddInitialFee
{
    /**
     * Context
     *
     * @var Context
     */
    private $context;

    /**
     * AddInitialFee constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    /**
     * @param Items $subject
     * @param array $result
     * @return array
     */
    public function afterGetColumns(Items $subject, $result)
    {
        $result = $this->context->arrayInsertBefore(
            $result,
            'total',
            ['tnw_subscriptions_initial_fee' => __('Initial Fee')]
        );

        return $result;
    }
}