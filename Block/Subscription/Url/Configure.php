<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Url;

/**
 * Class Configure - the configure url block
 */
class Configure extends \Magento\Framework\View\Element\Template
{
    /**
     * Configure product view blocks
     *
     * @return \Magento\Framework\View\Element\Template
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        // Set custom submit url route for form - to submit updated options to cart
        $block = $this->getLayout()->getBlock('product.info');
        if ($block) {
            $block->setSubmitRouteData(
                [
                    'route' => 'tnw_subscriptions/subscription_products/save',
                    'params' => ['sub_product_id' => $this->getRequest()->getParam('id')],
                ]
            );
        }

        return parent::_prepareLayout();
    }
}
