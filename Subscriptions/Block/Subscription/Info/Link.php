<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Info;
use TNW\Subscriptions\Model\SubscriptionProfile;

/**
 * Sales order link
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /** @var \Magento\Framework\Registry  */
    protected $_registry;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_registry = $registry;
    }

    /**
     * Retrieve current subscription model instance
     *
     * @return SubscriptionProfile
     */
    private function getSubscription()
    {
        return $this->_registry->registry('current_subscription');
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl($this->getPath(), ['entity_id' => $this->getSubscription()->getId()]);
    }
}
