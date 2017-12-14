<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Product\ListProduct;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Model\Config\Product\SubscriptionProductView;

/**
 *  Subscription Product list.
 */
class ListProductButtons extends Template
{
    /**
     * Subscription Product View Config model.
     *
     * @var SubscriptionProductView
     */
    private $subscriptionProductViewConfig;

    /**
     * @param Template\Context $context
     * @param SubscriptionProductView $subscriptionProductViewConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SubscriptionProductView $subscriptionProductViewConfig,
        array $data = []
    ) {
        $this->subscriptionProductViewConfig = $subscriptionProductViewConfig;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current product.
     *
     * @return ProductInterface|Product|null
     */
    public function getCurrentProduct()
    {
        return $this->getProduct() ? $this->getProduct() : null;
    }

    /**
     * Return position for actions regarding image size changing in vde if needed.
     *
     * @return string|null
     */
    public function getCurrentPosForActions()
    {
        return $this->getPos() ? $this->getPos() : null;
    }

    /**
     * Return current list view mode.
     *
     * @return string
     */
    public function getCurrentViewMode()
    {
        return $this->getViewMode() ? $this->getViewMode() : "";
    }

    /**
     * Return current product in list position.
     *
     * @return string
     */
    public function getCurrentPosition()
    {
        return $this->getPosition() ? $this->getPosition() : "";
    }

    /**
     * Return current product post params.
     *
     * @return array
     */
    public function getCurrentPostParams()
    {
        return $this->getPostParams() ? $this->getPostParams() : [];
    }

    /**
     * Get "Enable Subscriptions" config value for current website.
     *
     * @param ProductInterface|Product $product
     * @return bool
     */
    public function isSubscribeAvailable(ProductInterface $product)
    {
        return $this->subscriptionProductViewConfig->isSubscribeAvailable($product);
    }

    /**
     * Check if subscription purchase type is "Recurring purchase" only.
     *
     * @param ProductInterface|Product $product
     * @return bool
     */
    public function isOnlySubscribePurchase(ProductInterface $product)
    {
        return $this->subscriptionProductViewConfig->isOnlySubscribePurchase($product);
    }
}
