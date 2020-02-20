<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Cart;

/**
 * Block for cart.
 */
class Link extends \Magento\Framework\View\Element\Template
{
    /**
     * Get subscription cart url.
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->_urlBuilder->getUrl('tnw_subscriptions/checkout');
    }

    /**
     * Get shopping cart page url
     *
     * @return string
     */
    public function getShoppingCartUrl()
    {
        return $this->getUrl('tnw_subscriptions/cart');
    }

    /**
     * @return string
     */
    public function getSerializedConfig()
    {
        return \Zend_Json::encode([
            'websiteId' => $this->_storeManager->getStore()->getWebsiteId(),
            'shoppingCartUrl' => $this->getShoppingCartUrl(),
            'checkoutUrl' => $this->getCheckoutUrl(),
            'isRedirectRequired' => true,
            'customerLoginUrl' => $this->getUrl('customer/account/login'),
        ]);
    }

    public function _toHtml()
    {
        if (!$this->_scopeConfig->isSetFlag('tnw_subscriptions_general/general/show_magento_cart')) {
            return parent::_toHtml();
        }
    }
}
