<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Cart\Url;

class Add extends \Magento\Framework\View\Element\Template
{
    /**
     * Configure product view blocks
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareLayout()
    {
        /** @var \Magento\Catalog\Block\Product\View $block */
        $block = $this->getLayout()->getBlock('product.info');
        if ($block) {
            $urlParamName = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;

            $block->setData('submit_route_data', [
                'route' => 'tnw_subscriptions/cart/add',
                'params' => [
                    $urlParamName => strtr(base64_encode($this->_urlBuilder->getCurrentUrl()), '+/=', '-_,'),
                    'product' => $block->getProduct()->getEntityId(),
                    '_secure' => $this->getRequest()->isSecure()
                ],
            ]);
        }

        return parent::_prepareLayout();
    }
}
