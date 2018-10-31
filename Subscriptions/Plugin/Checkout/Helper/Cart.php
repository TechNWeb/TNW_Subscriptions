<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Checkout\Helper;

class Cart
{
    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    private $urlEncoder;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    public function __construct(
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->urlEncoder = $urlEncoder;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * @param \Magento\Checkout\Helper\Cart $subject
     * @param callable $callback
     * @param \Magento\Catalog\Model\Product $product
     * @param array $additional
     *
     * @return mixed
     */
    public function aroundGetAddUrl(
        \Magento\Checkout\Helper\Cart $subject,
        callable $callback,
        $product,
        $additional = []
    ) {
        if (isset($additional['useUencPlaceholder'])) {
            $uenc = '%uenc%';
            unset($additional['useUencPlaceholder']);
        } else {
            $uenc = $this->urlEncoder->encode($this->urlBuilder->getCurrentUrl());
        }

        $urlParamName = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;

        $routeParams = [
            $urlParamName => $uenc,
            'product' => $product->getEntityId(),
            '_secure' => $this->request->isSecure()
        ];

        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }

        if ($product->hasUrlDataObject()) {
            $routeParams['_scope'] = $product->getUrlDataObject()->getStoreId();
            $routeParams['_scope_to_url'] = true;
        }

        if ($this->request->getRouteName() === 'tnw_subscriptions'
            && $this->request->getControllerName() === 'cart'
        ) {
            $routeParams['in_cart'] = 1;
        }

        return $this->urlBuilder->getUrl('tnw_subscriptions/cart/add', $routeParams);
    }
}
