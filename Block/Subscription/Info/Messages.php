<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Info;

use Magento\Backend\Block\Template\Context;
use TNW\Subscriptions\Model\MessagePool;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Subscription tab messages block.
 */
class Messages extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'TNW_Subscriptions::subscription/messages.phtml';

    /**
     * @var MessagePool
     */
    private $messagePool;

    /**
     * @param Context     $context
     * @param MessagePool $messagePool
     * @param array       $data
     */
    public function __construct(Context $context, MessagePool $messagePool, array $data = [])
    {
        $this->messagePool = $messagePool;
        parent::__construct($context, $data);
    }

    /**
     * Returns messages by type.
     *
     * @param string $type
     * @return string[]
     */
    public function getGroupMessages($type)
    {
        return $this->messagePool->getMessages($type);
    }

    /**
     * Returns messages types.
     *
     * @return array
     */
    public function getMessageTypes()
    {
        return $this->messagePool->getMessageTypes();
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        $html = '';

        if ($this->messagePool->hasMessages()) {
            $html = parent::_toHtml();
        }

        return $html;
    }
}
