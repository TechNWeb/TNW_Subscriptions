<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block;

use Magento\Checkout\Block\Cart\Sidebar;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\Checkout\CompositeConfigProvider;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Cart block.
 */
class Cart extends Template
{
    /**
     * @var CompositeConfigProvider
     */
    private $configProvider;

    /**
     * @var Sidebar
     */
    private $sidebar;

    /**
     * @var QuoteSessionInterface
     */
    private $quoteSession;

    /**
     * Cart constructor.
     *
     * @param QuoteSessionInterface $quoteSession
     * @param Context $context
     * @param CompositeConfigProvider $configProvider
     * @param Sidebar $sidebar
     * @param array $data
     */
    public function __construct(
        QuoteSessionInterface $quoteSession,
        Context $context,
        CompositeConfigProvider $configProvider,
        Sidebar $sidebar,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteSession = $quoteSession;
        $this->configProvider = $configProvider;
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout'])
            ? $data['jsLayout']
            : [];
        $this->sidebar = $sidebar;
    }

    /**
     * Get JS layout
     *
     * @return string
     */
    public function getJsLayout()
    {
        //set config data
        $this->jsLayout['components']['subscriptionsProvider'] = $this->getCheckoutConfig();

        return \Zend_Json::encode($this->jsLayout);
    }

    /**
     * Get subscriptions checkout configuration
     *
     * @return array
     */
    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }

    /**
     * Get cart items count.
     *
     * @return int|float
     */
    public function getCartItemsCount()
    {
        return $this->quoteSession->getSubQuoteItemsCount();
    }

    /**
     * Get continue shopping url
     *
     * @return string
     */
    public function getContinueShoppingUrl()
    {
        return $this->_urlBuilder->getUrl();
    }

    /**
     * Get config from sidebar block for checkout data.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->sidebar->getConfig();
    }
}
