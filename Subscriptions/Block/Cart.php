<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block;

use Magento\Checkout\Block\Cart\Sidebar;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use TNW\Subscriptions\Model\QuoteSessionInterface;

/**
 * Cart block.
 */
class Cart extends Template
{
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
     * @param Sidebar $sidebar
     * @param array $data
     */
    public function __construct(
        QuoteSessionInterface $quoteSession,
        Context $context,
        Sidebar $sidebar,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->quoteSession = $quoteSession;
        $this->sidebar = $sidebar;
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

    /**
     * Return list of available checkout methods
     *
     * @param string $alias Container block alias in layout
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMethods($alias)
    {
        $childName = $this->getLayout()->getChildName($this->getNameInLayout(), $alias);
        if ($childName) {
            return $this->getLayout()->getChildNames($childName);
        }

        return [];
    }

    /**
     * Return HTML of checkout method (link, button etc.)
     *
     * @param string $name Block name in layout
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMethodHtml($name)
    {
        $block = $this->getLayout()->getBlock($name);
        if (!$block) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid method: %1', $name));
        }

        return $block->toHtml();
    }
}
