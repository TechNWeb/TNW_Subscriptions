<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block;

use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\Checkout\CompositeConfigProvider;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Cart block.
 */
class Cart extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CompositeConfigProvider
     */
    private $configProvider;

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
     * @param array $data
     */
    public function __construct(
        QuoteSessionInterface $quoteSession,
        Context $context,
        CompositeConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteSession = $quoteSession;
        $this->configProvider = $configProvider;
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout'])
            ? $data['jsLayout']
            : [];
    }

    /**
     * Get JS layout
     *
     * @return string
     */
    public function getJsLayout()
    {
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
}
