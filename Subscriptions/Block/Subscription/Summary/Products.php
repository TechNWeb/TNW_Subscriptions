<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Block\Subscription\Summary;

use Magento\Framework\View\Element\Template;
use TNW\Subscriptions\Api\Data\ProductSubscriptionProfileInterface;

/**
 * @method \TNW\Subscriptions\Model\SubscriptionProfile getSubscriptionProfile()
 */
class Products extends Template
{
    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    private $imageBuilder;

    public function __construct(
        Template\Context $context,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->imageBuilder = $imageBuilder;
    }

    /**
     * @return ProductSubscriptionProfileInterface[]
     */
    public function getProducts()
    {
        return $this->getSubscriptionProfile()->getProducts();
    }

    /**
     * Retrieve product image
     *
     * @param ProductSubscriptionProfileInterface $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($product->getMagentoProduct())
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }
}