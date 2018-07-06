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
    public function getCartUrl()
    {
        return $this->_urlBuilder->getUrl('tnw_subscriptions/cart/index');
    }
}
