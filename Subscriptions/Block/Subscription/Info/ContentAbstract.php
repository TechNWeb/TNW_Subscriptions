<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Info;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\MessagePool;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Subscription tab base content block.
 */
class ContentAbstract extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var MessagePool
     */
    protected $messagePool;

    /**
     * @var \TNW\Subscriptions\Block\Subscription\Info\Tab\Messages
     */
    private $messagesBlock;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param MessagePool $messagePool
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        MessagePool $messagePool,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->messagePool = $messagePool;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _beforeToHtml()
    {
        /*if ($this instanceof Items) {
            $this->messagePool->addMessage('warning', 'WARNING TEST');
            $this->messagePool->addMessage('error', 'ERROR TEST');
            $this->messagePool->addMessage('success', 'SUCCESS TEST');
        }*/
        ObjectManager::getInstance()->get(
            \TNW\Subscriptions\Block\Subscription\Info\Messages\ExpireWarningProcessor::class
        )->process($this);

        return parent::_beforeToHtml();
    }

    /**
     * Return messages block.
     *
     * @return \TNW\Subscriptions\Block\Subscription\Info\Messages|bool
     */
    public function getTabMessagesBlock()
    {
        if (null === $this->messagesBlock) {
            $this->messagesBlock = $this->getLayout()->createBlock(
                \TNW\Subscriptions\Block\Subscription\Info\Messages::class,
                '',
                ['messagePool' => $this->messagePool]
            );
        }

        return $this->messagesBlock;
    }

    /**
     * Return messages HTML.
     *
     * @return string
     */
    public function getTabMessagesHtml()
    {
        return $this->getTabMessagesBlock()->toHtml();
    }

    /**
     * Retrieve current subscription model instance.
     *
     * @return SubscriptionProfile
     */
    public function getSubscriptionProfile()
    {
        return $this->registry->registry('tnw_subscription_profile');
    }
}
