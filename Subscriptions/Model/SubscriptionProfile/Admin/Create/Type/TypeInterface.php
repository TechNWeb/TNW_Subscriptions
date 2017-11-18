<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\Admin\Create\Type;

/**
 * Buy request modifiers interface
 */
interface TypeInterface
{
    /**
     * Updates subscription products buy requests.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     * @return array
     */
    public function modifyBuyRequests(array $products);

    /**
     * Returns full product subscription price.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $productData
     * @return string
     */
    public function getSubscriptionCustomPrice(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        array $productData
    );

    /**
     * Returns product subscription price.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param array $productData
     * @return float|string
     */
    public function getSubscriptionPrice(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        array $productData
    );
}
