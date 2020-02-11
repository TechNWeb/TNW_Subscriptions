<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartAddProduct implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->cart = $cart;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getData('product');

        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getData('request');

        if (!$request->getParam('subscribe_active', 0)) {
            // Add product
            $this->cart->addProduct($product, $request->getParams());

            $related = $request->getParam('related_product');
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
        }

        // Save
        $this->cart->save();
    }
}
