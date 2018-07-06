<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin\Sales\Block\Order\Items\Column\Renderer;

use Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer;
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
     * @param DefaultRenderer $subject
     * @param array $result
     * @return array
     */
    public function afterGetColumns(DefaultRenderer $subject, $result)
    {
        $result = $this->context->arrayInsertBefore(
            $result,
            'total',
            ['tnw_subscriptions_initial_fee' => 'tnw-subscriptions-initial-fee']
        );

        return $result;
    }
}